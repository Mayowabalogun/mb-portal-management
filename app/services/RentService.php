<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/RentRepository.php';
require_once APP_ROOT . '/Repository/UnitRepository.php';
require_once APP_ROOT . '/services/PaymentService.php';

final class RentService
{
    private RentRepository $rentRepo;
    private UnitRepository $unitRepo;
    private PaymentService $paymentService;

    public function __construct(?mysqli $db = null)
    {
        $db = $db ?? getConnection();
        $this->rentRepo = new RentRepository($db);
        $this->unitRepo = new UnitRepository($db);
        $this->paymentService = new PaymentService($db);
    }

    public function processPayment(int $rentId, int $tenantId, float $amount, string $method = 'Cash', string $paymentType = 'Rent'): bool
    {
        try {
            $this->paymentService->processPayment($rentId, $tenantId, $amount, $method, $paymentType);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function classifyPaymentForUnit(int $unitId): string
    {
        return match ($this->unitRepo->getStatus($unitId)) {
            'Occupied', 'Reserved' => 'rent',
            'Holdover-AtWill' => 'debt',
            'Holdover-Sufferance' => 'mesne',
            default => 'rent',
        };
    }
}
