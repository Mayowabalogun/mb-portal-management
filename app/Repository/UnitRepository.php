<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class UnitRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? getConnection();
    }

    public function getStatus(int $unitId): string
    {
        $stmt = $this->db->prepare('SELECT status FROM property_units WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new RuntimeException("Unit {$unitId} not found.");
        }

        return (string) $row['status'];
    }

    public function markReserved(int $unitId): void
    {
        $status = $this->getStatus($unitId);
        if (in_array($status, ['Reserved', 'Occupied'], true)) {
            return;
        }
        if ($status !== 'Vacant') {
            throw new RuntimeException("Unit {$unitId} cannot be reserved from status {$status}.");
        }

        $stmt = $this->db->prepare("UPDATE property_units SET status = 'Reserved', reserved_at = NOW() WHERE id = ? AND status = 'Vacant' LIMIT 1");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected !== 1) {
            throw new RuntimeException("Unit reservation failed for unit_id={$unitId}");
        }
    }

    public function markOccupied(int $unitId): void
    {
        $status = $this->getStatus($unitId);
        if ($status === 'Occupied') {
            return;
        }
        if (!in_array($status, ['Reserved', 'Holdover-AtWill', 'Holdover-Sufferance'], true)) {
            throw new RuntimeException("Invalid occupation transition from {$status}.");
        }

        $stmt = $this->db->prepare("UPDATE property_units SET status = 'Occupied', occupied_at = NOW() WHERE id = ? AND status != 'Occupied' LIMIT 1");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected !== 1) {
            throw new RuntimeException("Unit occupation failed for unit_id={$unitId}");
        }
    }

    public function markVacant(int $unitId): void
    {
        $stmt = $this->db->prepare("UPDATE property_units SET status = 'Vacant', reserved_at = NULL, occupied_at = NULL WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $stmt->close();
    }

    public function markHoldoverSufferance(int $unitId): void
    {
        $stmt = $this->db->prepare("UPDATE property_units SET status = 'Holdover-Sufferance' WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $stmt->close();
    }

    public function recalcPropertyOccupancy(int $propertyId): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(status = 'Occupied') AS occupied FROM property_units WHERE property_id = ? AND is_active = 1");
        $stmt->bind_param('i', $propertyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $total = (int) ($row['total'] ?? 0);
        $occupied = (int) ($row['occupied'] ?? 0);
        $status = 'Vacant';
        if ($total > 0 && $occupied === $total) {
            $status = 'Occupied';
        } elseif ($occupied > 0 && $occupied < $total) {
            $status = 'Partially Occupied';
        }

        $upd = $this->db->prepare('UPDATE properties SET occupancy_status = ? WHERE property_id = ? LIMIT 1');
        $upd->bind_param('si', $status, $propertyId);
        $upd->execute();
        $upd->close();
    }
}
