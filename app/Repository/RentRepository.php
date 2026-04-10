<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class RentRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? getConnection();
    }

    public function begin(): void { $this->db->begin_transaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollback(): void { $this->db->rollback(); }

    public function findById(int $rentId, bool $forUpdate = false): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->db->prepare("SELECT * FROM rents WHERE rent_id = ? LIMIT 1{$lock}");
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findActiveByUnitId(int $unitId): ?array
    {
        $stmt = $this->db->prepare("SELECT rent_id FROM rents WHERE unit_id = ? AND terminated_at IS NULL AND status IN ('Active','Reserved','Pending','Due') LIMIT 1");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO rents (tenant_id, property_id, unit_id, property_type, rent_per_annum, rent_period, security_fee, caution_deposit, t_rent, total_pay, balance_due, rent_payment, start_date, end_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
        $stmt->bind_param(
            'iiisddddddddss',
            $data['tenant_id'],
            $data['property_id'],
            $data['unit_id'],
            $data['property_type'],
            $data['rent_per_annum'],
            $data['rent_period'],
            $data['security_fee'],
            $data['caution_deposit'],
            $data['t_rent'],
            $data['total_pay'],
            $data['balance_due'],
            $data['rent_payment'],
            $data['start_date'],
            $data['end_date']
        );
        if (!$stmt->execute()) {
            throw new RuntimeException('Rent insert failed: ' . $stmt->error);
        }
        $id = (int) $this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function updateBalance(int $rentId, float $totalPay, float $balanceDue, float $rentPayment): bool
    {
        $stmt = $this->db->prepare('UPDATE rents SET total_pay = ?, balance_due = ?, rent_payment = rent_payment + ? WHERE rent_id = ?');
        $stmt->bind_param('dddi', $totalPay, $balanceDue, $rentPayment, $rentId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getRentForUpdate(int $rentId): ?array
    {
        $stmt = $this->db->prepare('SELECT rent_id, tenant_id, property_id, unit_id, total_pay, balance_due, terminated_at, rent_per_annum, rent_period, security_fee, caution_deposit FROM rents WHERE rent_id = ? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findForVerification(int $rentId): array
    {
        $stmt = $this->db->prepare('SELECT rent_id, t_rent, total_pay AS paid_toward_enforceable, balance_due FROM rents WHERE rent_id = ? LIMIT 1');
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: [];
    }
}
