<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class RentAlertRepository
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function hasAlertsTable(): bool
    {
        return $this->tableExists('rent_alerts');
    }

    public function getActiveAlertsByPriority(int $limit = 10): array
    {
        if ($this->tableExists('rent_alerts')) {
            $tableAlerts = $this->getAlertsFromTable($limit);
            if (!empty($tableAlerts)) {
                return $tableAlerts;
            }
        }

        return $this->computeAlertsFromRents($limit);
    }

    private function getAlertsFromTable(int $limit): array
    {
        try {
            $sql = "SELECT
                        ra.alert_id,
                        ra.lease_id,
                        ra.tenant_id,
                        ra.property_id,
                        ra.alert_type,
                        ra.priority,
                        ra.due_date,
                        ra.days_overdue,
                        COALESCE(ra.notice_sent, 0) AS notice_sent,
                        COALESCE(t.full_name, t.email, 'Unknown Tenant') AS tenant_name,
                        COALESCE(p.property_label, p.address, 'Unknown Property') AS property_address,
                        COALESCE(r.t_rent, r.balance_due, 0) AS rent_amount
                    FROM rent_alerts ra
                    LEFT JOIN tenants t ON t.tenant_id = ra.tenant_id
                    LEFT JOIN properties p ON p.property_id = ra.property_id
                    LEFT JOIN rents r ON r.rent_id = ra.lease_id
                    WHERE COALESCE(ra.is_resolved, 0) = 0
                    ORDER BY FIELD(ra.priority, 'critical', 'warning', 'info'), ra.due_date ASC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable $e) {
            error_log('RentAlertRepository::getAlertsFromTable error: ' . $e->getMessage());
            return [];
        }
    }

    private function computeAlertsFromRents(int $limit): array
    {
        try {
            $sql = "SELECT
                        r.rent_id AS lease_id,
                        r.tenant_id,
                        r.property_id,
                        r.end_date AS due_date,
                        GREATEST(DATEDIFF(CURDATE(), r.end_date), 0) AS days_overdue,
                        CASE
                            WHEN DATEDIFF(CURDATE(), r.end_date) >= 30 THEN 'default'
                            WHEN DATE_ADD(r.end_date, INTERVAL 5 DAY) < CURDATE() THEN 'overdue'
                            WHEN r.end_date = CURDATE() THEN 'due_today'
                            WHEN r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 'due_soon'
                            ELSE 'grace_period'
                        END AS alert_type,
                        CASE
                            WHEN DATEDIFF(CURDATE(), r.end_date) >= 30 THEN 'critical'
                            WHEN DATE_ADD(r.end_date, INTERVAL 5 DAY) < CURDATE() THEN 'critical'
                            WHEN r.end_date = CURDATE() THEN 'warning'
                            WHEN r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 'info'
                            ELSE 'warning'
                        END AS priority,
                        COALESCE(t.full_name, t.email, 'Unknown Tenant') AS tenant_name,
                        COALESCE(p.property_label, p.address, 'Unknown Property') AS property_address,
                        COALESCE(r.t_rent, r.rent_per_annum, r.balance_due, 0) AS rent_amount
                    FROM rents r
                    LEFT JOIN tenants t ON t.tenant_id = r.tenant_id
                    LEFT JOIN properties p ON p.property_id = r.property_id
                    WHERE COALESCE(r.balance_due, 0) > 0
                      AND r.status IN ('Occupied', 'Active', 'Due')
                    ORDER BY FIELD(
                        CASE
                            WHEN DATEDIFF(CURDATE(), r.end_date) >= 30 THEN 'critical'
                            WHEN DATE_ADD(r.end_date, INTERVAL 5 DAY) < CURDATE() THEN 'critical'
                            WHEN r.end_date = CURDATE() THEN 'warning'
                            ELSE 'info'
                        END, 'critical', 'warning', 'info'
                    ), r.end_date ASC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable $e) {
            error_log('RentAlertRepository::computeAlertsFromRents error: ' . $e->getMessage());
            return [];
        }
    }

    public function countByPriority(string $priority): int
    {
        if ($this->tableExists('rent_alerts')) {
            try {
                $sql = "SELECT COUNT(*) AS total FROM rent_alerts WHERE priority = ? AND COALESCE(is_resolved, 0) = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('s', $priority);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $tableCount = (int) ($row['total'] ?? 0);
                if ($tableCount > 0) {
                    return $tableCount;
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        try {
            $conditions = [
                'critical' => "DATEDIFF(CURDATE(), end_date) >= 30 OR DATE_ADD(end_date, INTERVAL 5 DAY) < CURDATE()",
                'warning' => "end_date = CURDATE() OR (end_date < CURDATE() AND DATE_ADD(end_date, INTERVAL 5 DAY) >= CURDATE())",
                'info' => "end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)",
            ];
            if (!isset($conditions[$priority])) {
                return 0;
            }

            $sql = "SELECT COUNT(*) AS total
                    FROM rents
                    WHERE ({$conditions[$priority]})
                      AND COALESCE(balance_due, 0) > 0
                      AND status IN ('Occupied', 'Active', 'Due')";
            $res = $this->conn->query($sql);
            if (!$res) {
                return 0;
            }
            $row = $res->fetch_assoc();
            return (int) ($row['total'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function countUnresolved(): int
    {
        if ($this->tableExists('rent_alerts')) {
            try {
                $res = $this->conn->query('SELECT COUNT(*) AS total FROM rent_alerts WHERE COALESCE(is_resolved, 0) = 0');
                if (!$res) {
                    return 0;
                }
                $row = $res->fetch_assoc();
                $tableCount = (int) ($row['total'] ?? 0);
                if ($tableCount > 0) {
                    return $tableCount;
                }
            } catch (Throwable) {
                // fallback below
            }
        }

        try {
            $res = $this->conn->query("SELECT COUNT(*) AS total FROM rents WHERE COALESCE(balance_due, 0) > 0 AND status IN ('Occupied', 'Active', 'Due')");
            if (!$res) {
                return 0;
            }
            $row = $res->fetch_assoc();
            return (int) ($row['total'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function markResolved(int $leaseId): bool
    {
        if (!$this->tableExists('rent_alerts')) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare('UPDATE rent_alerts SET is_resolved = 1, resolved_at = NOW() WHERE lease_id = ? AND COALESCE(is_resolved, 0) = 0');
            $stmt->bind_param('i', $leaseId);
            return $stmt->execute();
        } catch (Throwable) {
            return false;
        }
    }

    public function getOutstandingAmount(int $leaseId): float
    {
        try {
            $stmt = $this->conn->prepare('SELECT COALESCE(balance_due, balance, 0) AS outstanding FROM rents WHERE rent_id = ? LIMIT 1');
            $stmt->bind_param('i', $leaseId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return (float) ($row['outstanding'] ?? 0);
        } catch (Throwable) {
            return 0.0;
        }
    }

    public function upsertComputedAlerts(): int
    {
        if (!$this->tableExists('rent_alerts')) {
            return 0;
        }

        $inserted = 0;
        $rows = $this->fetchComputedAlertRows();

        foreach ($rows as $row) {
            try {
                $sql = "INSERT INTO rent_alerts
                        (lease_id, tenant_id, property_id, alert_type, priority, due_date, grace_end_date, days_overdue, notice_sent, is_resolved)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
                        ON DUPLICATE KEY UPDATE
                          alert_type = VALUES(alert_type),
                          priority = VALUES(priority),
                          days_overdue = VALUES(days_overdue),
                          grace_end_date = VALUES(grace_end_date),
                          is_resolved = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    'iiissssi',
                    $row['lease_id'],
                    $row['tenant_id'],
                    $row['property_id'],
                    $row['alert_type'],
                    $row['priority'],
                    $row['due_date'],
                    $row['grace_end_date'],
                    $row['days_overdue']
                );
                if ($stmt->execute()) {
                    $inserted += 1;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $inserted;
    }

    private function fetchComputedAlertRows(): array
    {
        try {
            $sql = "SELECT
                        r.rent_id AS lease_id,
                        r.tenant_id,
                        r.property_id,
                        r.end_date AS due_date,
                        DATE_ADD(r.end_date, INTERVAL 5 DAY) AS grace_end_date,
                        GREATEST(DATEDIFF(CURDATE(), r.end_date), 0) AS days_overdue,
                        CASE
                            WHEN DATEDIFF(CURDATE(), r.end_date) >= 30 THEN 'default'
                            WHEN DATE_ADD(r.end_date, INTERVAL 5 DAY) < CURDATE() THEN 'overdue'
                            WHEN r.end_date = CURDATE() THEN 'due_today'
                            WHEN r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 'due_soon'
                            ELSE 'grace_period'
                        END AS alert_type,
                        CASE
                            WHEN DATEDIFF(CURDATE(), r.end_date) >= 30 THEN 'critical'
                            WHEN DATE_ADD(r.end_date, INTERVAL 5 DAY) < CURDATE() THEN 'critical'
                            WHEN r.end_date = CURDATE() THEN 'warning'
                            WHEN r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) THEN 'info'
                            ELSE 'warning'
                        END AS priority
                    FROM rents r
                    WHERE COALESCE(r.balance_due, 0) > 0
                      AND r.status IN ('Occupied', 'Active', 'Due')";
            $res = $this->conn->query($sql);
            if (!$res) {
                return [];
            }
            return $res->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable $e) {
            error_log('RentAlertRepository::fetchComputedAlertRows error: ' . $e->getMessage());
            return [];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->conn->prepare('SHOW TABLES LIKE ?');
            $stmt->bind_param('s', $table);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
