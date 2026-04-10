<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';
require_once APP_ROOT . '/services/AuditService.php';

class RentExpiryRepository
{
    private mysqli $conn;
    private const EXPIRED_UNIT_STATUSES = "'Occupied','Holdover-AtWill','Holdover-Sufferance'";
    private const ACTIVE_RENT_STATES = "'Active','Due','Occupied'";

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function findExpiredRents(): array
    {
        $leaseStateSql = $this->leaseStateSql();
        $sql = "SELECT
                    r.rent_id, r.tenant_id, r.unit_id, r.property_id,
                    r.start_date, r.end_date, r.t_rent, r.balance_due,
                    r.status AS rent_status,
                    t.full_name AS tenant_name, t.phone AS tenant_phone, t.email AS tenant_email,
                    p.property_label, p.address AS property_address, p.town_city, p.state,
                    u.unit_label, u.unit_type, u.status AS unit_status,
                    {$leaseStateSql} AS lease_state,
                    DATEDIFF(CURDATE(), r.end_date) AS days_expired
                FROM rents r
                INNER JOIN tenants t ON t.tenant_id = r.tenant_id
                INNER JOIN properties p ON p.property_id = r.property_id
                INNER JOIN property_units u ON u.id = r.unit_id
                WHERE r.end_date < CURDATE()
                    AND r.status IN (" . self::ACTIVE_RENT_STATES . ")
                    AND u.status IN (" . self::EXPIRED_UNIT_STATUSES . ")
                    AND p.is_active = 1
                ORDER BY r.end_date ASC, days_expired DESC";

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findExpiredRentsByLandlord(int $landlordId): array
    {
        $leaseStateSql = $this->leaseStateSql();
        $sql = "SELECT
                    r.rent_id, r.tenant_id, r.unit_id, r.property_id,
                    r.start_date, r.end_date, r.t_rent, r.balance_due,
                    r.status AS rent_status,
                    t.full_name AS tenant_name, t.phone AS tenant_phone, t.email AS tenant_email,
                    p.property_label, p.address AS property_address, p.town_city, p.state,
                    u.unit_label, u.unit_type, u.status AS unit_status,
                    {$leaseStateSql} AS lease_state,
                    DATEDIFF(CURDATE(), r.end_date) AS days_expired
                FROM rents r
                INNER JOIN tenants t ON t.tenant_id = r.tenant_id
                INNER JOIN properties p ON p.property_id = r.property_id
                INNER JOIN property_units u ON u.id = r.unit_id
                WHERE r.end_date < CURDATE()
                    AND r.status IN (" . self::ACTIVE_RENT_STATES . ")
                    AND u.status IN (" . self::EXPIRED_UNIT_STATUSES . ")
                    AND p.is_active = 1
                    AND p.landlord_id = ?
                ORDER BY r.end_date ASC, days_expired DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $landlordId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countExpiredRents(): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM rents r
                INNER JOIN property_units u ON u.id = r.unit_id
                INNER JOIN properties p ON p.property_id = r.property_id
                WHERE r.end_date < CURDATE()
                    AND r.status IN (" . self::ACTIVE_RENT_STATES . ")
                    AND u.status IN (" . self::EXPIRED_UNIT_STATUSES . ")
                    AND p.is_active = 1";

        $result = $this->conn->query($sql);
        return (int) (($result?->fetch_assoc()['total']) ?? 0);
    }

    public function countExpiredByLandlord(int $landlordId): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM rents r
                INNER JOIN property_units u ON u.id = r.unit_id
                INNER JOIN properties p ON p.property_id = r.property_id
                WHERE r.end_date < CURDATE()
                    AND r.status IN (" . self::ACTIVE_RENT_STATES . ")
                    AND u.status IN (" . self::EXPIRED_UNIT_STATUSES . ")
                    AND p.is_active = 1
                    AND p.landlord_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $landlordId);
        $stmt->execute();
        return (int) (($stmt->get_result()->fetch_assoc()['total']) ?? 0);
    }

    public function getExpirySummaryByType(): array
    {
        $sql = "SELECT
                    u.unit_type,
                    COUNT(*) as expired_count,
                    SUM(r.balance_due) as total_outstanding
                FROM rents r
                INNER JOIN property_units u ON u.id = r.unit_id
                INNER JOIN properties p ON p.property_id = r.property_id
                WHERE r.end_date < CURDATE()
                    AND r.status IN (" . self::ACTIVE_RENT_STATES . ")
                    AND u.status IN (" . self::EXPIRED_UNIT_STATUSES . ")
                    AND p.is_active = 1
                GROUP BY u.unit_type";

        $result = $this->conn->query($sql);
        $results = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $types = ['flat', 'apartment', 'room', 'shop', 'hostel', 'office', 'warehouse', 'shortlet', 'bungalow'];
        $summary = array_fill_keys($types, ['expired_count' => 0, 'total_outstanding' => 0.00]);

        foreach ($results as $row) {
            $type = $row['unit_type'];
            if (array_key_exists($type, $summary)) {
                $summary[$type] = [
                    'expired_count' => (int) $row['expired_count'],
                    'total_outstanding' => (float) $row['total_outstanding'],
                ];
            }
        }

        return $summary;
    }

    public function verifyRentOwnership(int $rentId, ?int $landlordId = null): ?array
    {
        $sql = "SELECT r.rent_id, r.unit_id, r.tenant_id, r.status, r.end_date
                FROM rents r
                JOIN properties p ON p.property_id = r.property_id
                WHERE r.rent_id = ? ";

        $params = [$rentId];
        $types = 'i';

        if ($landlordId !== null) {
            $sql .= 'AND p.landlord_id = ? ';
            $params[] = $landlordId;
            $types .= 'i';
        }

        $sql .= 'LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function executeVacate(int $rentId, int $unitId, int $tenantId): bool
    {
        $snapshot = $this->getVacateSnapshot($rentId);
        $this->conn->begin_transaction();

        try {
            $stmt1 = $this->conn->prepare("UPDATE rents
                SET status = 'Vacated', terminated_at = NOW(),
                    termination_reason = 'expiry', updated_at = NOW()
                WHERE rent_id = ?");
            $stmt1->bind_param('i', $rentId);
            $stmt1->execute();

            $stmt2 = $this->conn->prepare("UPDATE property_units
                SET status = 'Vacant', occupied_at = NULL, last_update = NOW()
                WHERE id = ?");
            $stmt2->bind_param('i', $unitId);
            $stmt2->execute();

            $stmt3 = $this->conn->prepare("UPDATE tenants
                SET tenancy_status = 'vacated', updated_at = NOW()
                WHERE tenant_id = ?");
            $stmt3->bind_param('i', $tenantId);
            $stmt3->execute();

            $stmt4 = $this->conn->prepare("INSERT INTO reports (
                    rent_id, tenant_id, property_id, unit_id, property_type,
                    start_date, end_date, total_rent, total_paid, balance_due,
                    status, report_date, rent_type, created_at
                )
                SELECT r.rent_id, r.tenant_id, r.property_id, r.unit_id,
                    u.unit_type, r.start_date, r.end_date, r.t_rent,
                    r.total_pay, r.balance_due, 'Vacated',
                    CURDATE(), 'Vacated', NOW()
                FROM rents r
                JOIN property_units u ON u.id = r.unit_id
                WHERE r.rent_id = ?");
            $stmt4->bind_param('i', $rentId);
            $stmt4->execute();

            $this->conn->commit();

            AuditService::instance()->mutate('rent.vacated', $rentId, 'rent', [
                'status' => (string) ($snapshot['rent_status'] ?? 'unknown'),
                'termination_reason' => (string) ($snapshot['termination_reason'] ?? ''),
                'unit_status' => (string) ($snapshot['unit_status'] ?? 'unknown'),
                'tenant_status' => (string) ($snapshot['tenant_status'] ?? 'unknown'),
            ], [
                'status' => 'Vacated',
                'termination_reason' => 'expiry',
                'unit_status' => 'Vacant',
                'tenant_status' => 'vacated',
            ], 'Vacated due to lease expiry');

            AuditService::instance()->record('unit.status.changed', $unitId, 'unit', [
                'rent_id' => $rentId,
                'from' => (string) ($snapshot['unit_status'] ?? 'unknown'),
                'to' => 'Vacant',
                'source' => 'rent_expiry_vacate',
            ]);

            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            AuditService::instance()->failed('rent.vacated', 'Vacate transaction failed', [
                'entity_type' => 'rent',
                'entity_id' => $rentId,
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            error_log("Vacate failed [rent:{$rentId}]: " . $e->getMessage());
            return false;
        }
    }

    public function getLeaseStateSummary(?int $landlordId = null): array
    {
        $stateSql = $this->leaseStateSql();
        $sql = "SELECT
                    {$stateSql} AS lease_state,
                    COUNT(*) AS total
                FROM rents r
                INNER JOIN properties p ON p.property_id = r.property_id
                INNER JOIN property_units u ON u.id = r.unit_id
                WHERE p.is_active = 1
                  AND r.status IN ('Active', 'Due', 'Occupied', 'Vacated')";

        $params = [];
        if ($landlordId !== null) {
            $sql .= " AND p.landlord_id = ?";
            $params[] = $landlordId;
        }

        $sql .= " GROUP BY lease_state";

        if ($params === []) {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $params[0]);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function leaseStateSql(): string
    {
        return "CASE
                    WHEN r.status = 'Vacated' OR r.terminated_at IS NOT NULL OR u.status = 'Vacant' THEN 'VACATED'
                    WHEN r.end_date < CURDATE() AND u.status IN ('Holdover-AtWill','Holdover-Sufferance') THEN 'HOLDOVER'
                    WHEN r.end_date < CURDATE() THEN 'EXPIRED'
                    WHEN r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'EXPIRING_SOON'
                    ELSE 'ACTIVE'
                END";
    }

    private function getVacateSnapshot(int $rentId): array
    {
        $sql = "SELECT
                    r.status AS rent_status,
                    r.termination_reason,
                    u.status AS unit_status,
                    t.tenancy_status AS tenant_status
                FROM rents r
                INNER JOIN property_units u ON u.id = r.unit_id
                INNER JOIN tenants t ON t.tenant_id = r.tenant_id
                WHERE r.rent_id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: [];
    }
}
