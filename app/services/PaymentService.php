<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/PaymentRepository.php';
require_once APP_ROOT . '/Repository/RentRepository.php';
require_once APP_ROOT . '/Repository/UnitRepository.php';
require_once APP_ROOT . '/modules/RentModel.php';
require_once APP_ROOT . '/services/AuditService.php';

class PaymentService
{
    private mysqli $db;
    private PaymentRepository $paymentRepo;
    private RentRepository $rentRepo;
    private UnitRepository $unitRepo;
    private RentModel $rentModel;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? getConnection();
        $this->paymentRepo = new PaymentRepository($this->db);
        $this->rentRepo = new RentRepository($this->db);
        $this->unitRepo = new UnitRepository($this->db);
        $this->rentModel = new RentModel();
    }

    public function processPayment(int $rentId, int $tenantId, float $amount, string $method = 'Cash', string $paymentType = 'Rent'): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment must be greater than zero.');
        }

        $this->db->begin_transaction();
        try {
            $rent = $this->rentRepo->getRentForUpdate($rentId);
            if (!$rent || !empty($rent['terminated_at'])) {
                throw new RuntimeException('Rent is inactive or missing.');
            }

            // Reserve the unit on any payment (idempotent/no-op if already reserved/occupied)
            try {
                $this->unitRepo->markReserved((int) $rent['unit_id']);
            } catch (Throwable $e) {
                error_log("[UNIT_RESERVE] rent={$rentId} unit={$rent['unit_id']}: {$e->getMessage()}");
            }

            $paymentId = $this->paymentRepo->insert([
                'rent_id' => $rentId,
                'amount' => $amount,
                'payment_date' => date('Y-m-d'),
                'payment_method' => $method,
                'payment_type' => $paymentType,
                'status' => 'completed',
                'paid_by_tenant_id' => $tenantId,
            ]);

            $payment = $this->paymentRepo->findByRentId($rentId);
            $last = end($payment);
            $receiptNo = (string) ($last['receipt_no'] ?? '');

            $totalPay = $this->rentModel->calculateTotalPaid((float) ($rent['total_pay'] ?? 0), $amount);
            $balance = $this->rentModel->calculateBalance((float) ($rent['balance_due'] ?? 0), $amount);
            $this->rentRepo->updateBalance($rentId, $totalPay, $balance, $amount);

            // Fully settled rent transitions unit to occupied before agreement stage.
            if ($balance <= 0.001) {
                try {
                    $this->unitRepo->markOccupied((int) $rent['unit_id']);
                    $this->unitRepo->recalcPropertyOccupancy((int) ($rent['property_id'] ?? 0));
                } catch (Throwable $e) {
                    error_log("[UNIT_OCCUPY] rent={$rentId} unit={$rent['unit_id']}: {$e->getMessage()}");
                }
            }

            $this->db->commit();

            AuditService::instance()->record('payment.recorded', $paymentId, 'payment', [
                'rent_id' => $rentId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'method' => $method,
                'payment_type' => $paymentType,
                'receipt_no' => $receiptNo,
            ], 'Payment processed via PaymentService');

            AuditService::instance()->mutate('rent.balance.updated', $rentId, 'rent', [
                'total_pay' => (float) ($rent['total_pay'] ?? 0),
                'balance_due' => (float) ($rent['balance_due'] ?? 0),
            ], [
                'total_pay' => $totalPay,
                'balance_due' => $balance,
            ], 'Updated from unified payment service');

            return ['success' => true, 'payment_id' => $paymentId, 'balance_due' => $balance, 'receipt_no' => $receiptNo];
        } catch (Throwable $e) {
            $this->db->rollback();
            AuditService::instance()->failed('payment.recorded', 'PaymentService failed', [
                'entity_type' => 'payment',
                'rent_id' => $rentId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
