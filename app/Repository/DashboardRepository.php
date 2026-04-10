<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class DashboardRepository
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function countProperties(): int { return $this->scalarInt('SELECT COUNT(*) FROM properties'); }
    public function countActiveProperties(): int { return $this->scalarInt("SELECT COUNT(*) FROM properties WHERE is_active = 1"); }
    public function countPropertyUnits(): int { return $this->scalarInt('SELECT COUNT(*) FROM property_units'); }
    public function countOccupiedUnits(): int { return $this->scalarInt("SELECT COUNT(*) FROM property_units WHERE status = 'Occupied'"); }
    public function countVacantUnits(): int { return $this->scalarInt("SELECT COUNT(*) FROM property_units WHERE status = 'Vacant'"); }
    public function countActiveLandlords(): int { return $this->scalarInt('SELECT COUNT(*) FROM landlords'); }
    public function countActiveTenants(): int { return $this->scalarInt('SELECT COUNT(*) FROM tenants'); }
    public function countNewTenantsThisMonth(): int { return $this->scalarInt('SELECT COUNT(*) FROM tenants WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())'); }
    public function countRentByStatus(string $status): int {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM rents WHERE status = ?');
        $stmt->bind_param('s', $status);
        $stmt->execute();
        return (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }
    public function countOpenMaintenanceRequests(): int { return $this->scalarInt("SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('pending','in_progress')"); }
    public function countMaintenanceByPriority(string $priority): int {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM maintenance_requests WHERE priority = ?');
        $stmt->bind_param('s', $priority);
        $stmt->execute();
        return (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }
    public function countActiveUsers(): int { return $this->scalarIntIfTable('portal_accounts', "SELECT COUNT(*) FROM portal_accounts WHERE status='active'"); }
    public function countActiveVendors(): int { return $this->scalarIntIfTable('vendors', "SELECT COUNT(*) FROM vendors WHERE status = 'Active'"); }
    public function countActivePartners(): int { return $this->scalarIntIfTable('partners', "SELECT COUNT(*) FROM partners WHERE status = 'Active'"); }
    public function countActiveProjects(): int { return $this->scalarIntIfTable('projects', "SELECT COUNT(*) FROM projects WHERE status='active'"); }
    public function countPendingTasks(): int { return $this->scalarIntIfTable('tasks', "SELECT COUNT(*) FROM tasks WHERE status='pending'"); }

    public function getRecentLogins(int $limit = 10): array {
        try {
            $stmt = $this->conn->prepare('SELECT username, level, success, attempted_at FROM login_telemetry ORDER BY attempted_at DESC LIMIT ?');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    public function getProjectStats(): array { return ['active' => $this->countActiveProjects(), 'pending_tasks' => $this->countPendingTasks()]; }
    public function getVendorPerformanceMetrics(): array { return []; }
    public function getProjectCompletionStats(): array { return []; }
    public function getTeamUtilization(): array { return []; }

    public function getMonthlyRevenueTrend(int $months = 12): array {
        try {
            $stmt = $this->conn->prepare("SELECT DATE_FORMAT(payment_date, '%b %Y') AS month, SUM(amount) AS amount
                FROM rent_payments
                WHERE payment_date IS NOT NULL
                  AND payment_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY YEAR(payment_date), MONTH(payment_date)
                ORDER BY YEAR(payment_date), MONTH(payment_date)");
            $stmt->bind_param('i', $months);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
    public function getOccupancyTrend(int $months = 12): array { return [['month' => date('M Y'), 'rate' => $this->getCurrentOccupancyRate()]]; }
    public function getRentStatusDistribution(): array {
        return [
            'active' => $this->countRentByStatus('Active'),
            'due' => $this->countRentByStatus('Due'),
            'overdue' => $this->countRentByStatus('Overdue'),
            'expiring' => $this->countExpiringRents(),
        ];
    }
    public function countExpiringRents(): int { return $this->scalarInt("SELECT COUNT(*) FROM rents WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status='Active'"); }
    public function getMonthlyRevenue(): float { return $this->scalarFloat("SELECT SUM(amount) FROM rent_payments WHERE payment_date IS NOT NULL AND MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())"); }
    public function getTotalCollectedRent(): float { return $this->scalarFloat('SELECT SUM(total_pay) FROM rents'); }
    public function getOutstandingRent(): float { return $this->scalarFloat('SELECT SUM(balance_due) FROM rents WHERE balance_due > 0'); }
    public function getTotalExpenses(): float {
        if (!$this->tableExists('expenses')) {
            return 0.0;
        }
        return $this->scalarFloat('SELECT SUM(amount) FROM expenses');
    }
    public function getCollectionRate(): float {
        $totalDue = $this->scalarFloat("SELECT SUM(t_rent) FROM rents WHERE status='Active'");
        $collected = $this->getTotalCollectedRent();
        return $totalDue > 0 ? round(($collected / $totalDue) * 100, 1) : 0.0;
    }
    public function countUnitsUnderMaintenance(): int { return $this->scalarInt("SELECT COUNT(DISTINCT unit_id) FROM maintenance_requests WHERE status IN ('pending','in_progress')"); }

    public function getRecentPayments(int $limit = 10): array {
        try {
            $stmt = $this->conn->prepare('SELECT rp.amount, rp.payment_date, COALESCE(t.full_name, t.email, "Unknown") AS tenant_name
                FROM rent_payments rp
                JOIN rents r ON rp.rent_id = r.rent_id
                JOIN tenants t ON r.tenant_id = t.tenant_id
                ORDER BY rp.payment_date DESC LIMIT ?');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
    public function getRecentTenants(int $limit = 10): array {
        try {
            $stmt = $this->conn->prepare('SELECT full_name AS name, created_at FROM tenants ORDER BY created_at DESC LIMIT ?');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
    public function getRecentMaintenanceRequests(int $limit = 10): array {
        try {
            $stmt = $this->conn->prepare('SELECT mr.*, p.property_label AS property_name, u.unit_label AS unit_number
                FROM maintenance_requests mr
                JOIN property_units u ON mr.unit_id = u.id
                JOIN properties p ON mr.property_id = p.property_id
                ORDER BY mr.created_at DESC LIMIT ?');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    public function getSystemHealthMetrics(): array {
        return ['last_backup' => null, 'error_count_24h' => 0, 'queue_size' => 0];
    }

    public function getRentDueAlerts(int $limit = 5): array
    {
        try {
            $sql = 'SELECT
                        COALESCE(t.full_name, t.name, t.email, "Unknown Tenant") AS tenant,
                        COALESCE(p.property_label, p.address, "Unknown Property") AS property,
                        COALESCE(r.balance_due, r.balance, 0) AS amount
                    FROM rents r
                    LEFT JOIN tenants t ON r.tenant_id = t.tenant_id
                    LEFT JOIN properties p ON r.property_id = p.property_id
                    WHERE r.due_date <= CURDATE()
                      AND COALESCE(r.balance_due, r.balance, 0) > 0
                    ORDER BY r.due_date ASC
                    LIMIT ?';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    public function getDebtorAlerts(int $limit = 5): array
    {
        if (!$this->tableExists('debtors')) {
            return [];
        }

        try {
            $stmt = $this->conn->prepare('SELECT tenant_name AS tenant, balance
                FROM debtors
                WHERE balance > 0
                ORDER BY balance DESC
                LIMIT ?');
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }

    private function getCurrentOccupancyRate(): float {
        $total = $this->countPropertyUnits();
        $occupied = $this->countOccupiedUnits();
        return $total > 0 ? round(($occupied / $total) * 100, 1) : 0.0;
    }

    private function scalarInt(string $sql): int {
        try {
            $res = $this->conn->query($sql);
            if (!$res) return 0;
            $row = $res->fetch_row();
            return (int)($row[0] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    private function scalarIntIfTable(string $table, string $sql): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        return $this->scalarInt($sql);
    }

    private function scalarFloat(string $sql): float {
        try {
            $res = $this->conn->query($sql);
            if (!$res) return 0.0;
            $row = $res->fetch_row();
            return (float)($row[0] ?? 0);
        } catch (Throwable) {
            return 0.0;
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
