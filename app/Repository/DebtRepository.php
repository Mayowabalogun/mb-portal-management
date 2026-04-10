<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';
require_once APP_ROOT . '/services/AuditService.php';

class DebtRepository
{
    private mysqli $conn;
    private const ACTIVE_RENT_STATES = "'Occupied','Active','Due'";
    private const OVERDUE_DAYS_SQL = 'DATEDIFF(CURDATE(), r.end_date)';

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function getDebtors(int $limit = 25, int $offset = 0, string $category = 'all', string $search = ''): array
    {
        $sql = "SELECT
            r.rent_id,
            r.tenant_id,
            r.property_id,
            r.unit_id,
            t.full_name,
            COALESCE(t.phone, t.phone_raw) AS phone,
            t.email,
            t.address,
            t.occupation,
            p.property_label,
            u.unit_label,
            r.t_rent,
            r.total_pay,
            r.balance_due,
            r.start_date,
            r.end_date,
            r.status,
            " . self::OVERDUE_DAYS_SQL . " AS days_overdue,
            nok.phone AS guarantor_phone,
            nok.name AS guarantor_name
        FROM rents r
        JOIN tenants t ON t.tenant_id = r.tenant_id
        JOIN properties p ON p.property_id = r.property_id
        JOIN property_units u ON u.id = r.unit_id
        LEFT JOIN (
            SELECT tenant_id, name, phone
            FROM tenant_next_of_kins
            WHERE id IN (
                SELECT MIN(id)
                FROM tenant_next_of_kins
                GROUP BY tenant_id
            )
        ) nok ON nok.tenant_id = t.tenant_id
        WHERE COALESCE(r.balance_due, 0) > 0
          AND r.status IN (" . self::ACTIVE_RENT_STATES . ")";

        $conditions = [
            'soft' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 1 AND 30',
            'hard' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 31 AND 90',
            'critical' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 91 AND 365',
            'legal' => ' AND ' . self::OVERDUE_DAYS_SQL . ' > 365',
            'outstanding' => ' AND ' . self::OVERDUE_DAYS_SQL . ' <= 0',
        ];

        if (isset($conditions[$category])) {
            $sql .= $conditions[$category];
        }

        if ($search !== '') {
            $sql .= ' AND (t.full_name LIKE ? OR t.email LIKE ? OR p.property_label LIKE ? OR u.unit_label LIKE ? OR t.phone LIKE ?)';
        }

        $sql .= ' ORDER BY r.balance_due DESC, days_overdue DESC LIMIT ? OFFSET ?';

        $stmt = $this->conn->prepare($sql);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt->bind_param('sssssii', $like, $like, $like, $like, $like, $limit, $offset);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countDebtors(string $category = 'all', string $search = ''): int
    {
        $sql = "SELECT COUNT(*) AS total
            FROM rents r
            JOIN tenants t ON t.tenant_id = r.tenant_id
            JOIN properties p ON p.property_id = r.property_id
            JOIN property_units u ON u.id = r.unit_id
            WHERE COALESCE(r.balance_due, 0) > 0
              AND r.status IN (" . self::ACTIVE_RENT_STATES . ")";

        $conditions = [
            'soft' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 1 AND 30',
            'hard' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 31 AND 90',
            'critical' => ' AND ' . self::OVERDUE_DAYS_SQL . ' BETWEEN 91 AND 365',
            'legal' => ' AND ' . self::OVERDUE_DAYS_SQL . ' > 365',
            'outstanding' => ' AND ' . self::OVERDUE_DAYS_SQL . ' <= 0',
        ];

        if (isset($conditions[$category])) {
            $sql .= $conditions[$category];
        }

        if ($search !== '') {
            $sql .= ' AND (t.full_name LIKE ? OR t.email LIKE ? OR p.property_label LIKE ? OR u.unit_label LIKE ? OR t.phone LIKE ?)';
        }

        $stmt = $this->conn->prepare($sql);
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    public function getDebtSummary(): array
    {
        $sql = "SELECT
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 AND DATEDIFF(CURDATE(), end_date) BETWEEN 1 AND 30 THEN 1 ELSE 0 END) AS soft_count,
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 AND DATEDIFF(CURDATE(), end_date) BETWEEN 31 AND 90 THEN 1 ELSE 0 END) AS hard_count,
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 AND DATEDIFF(CURDATE(), end_date) BETWEEN 91 AND 365 THEN 1 ELSE 0 END) AS critical_count,
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 AND DATEDIFF(CURDATE(), end_date) > 365 THEN 1 ELSE 0 END) AS legal_count,
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 AND DATEDIFF(CURDATE(), end_date) <= 0 THEN 1 ELSE 0 END) AS outstanding_count,
            SUM(CASE WHEN COALESCE(balance_due,0) > 0 THEN balance_due ELSE 0 END) AS total_outstanding,
            COUNT(*) AS total_rents
        FROM rents
        WHERE status IN (" . self::ACTIVE_RENT_STATES . ")";

        $res = $this->conn->query($sql);
        return $res ? ($res->fetch_assoc() ?: []) : [];
    }

    public function getDebtByRentId(int $rentId): ?array
    {
        $sql = "SELECT r.rent_id, r.tenant_id, r.property_id, r.unit_id, r.t_rent, r.total_pay, r.balance_due,
                       r.start_date, r.end_date, r.status,
                       COALESCE(t.full_name, t.email) AS tenant_name,
                       COALESCE(t.phone, t.phone_raw) AS phone,
                       t.email AS tenant_email,
                       t.address AS tenant_address,
                       t.occupation,
                       tg.name AS guarantor_name,
                       tg.phone AS guarantor_phone,
                       tg.relationship AS guarantor_relationship,
                       nok.name AS nok_name,
                       nok.phone AS nok_phone,
                       nok.relationship AS nok_relationship,
                       COALESCE(p.property_label, p.address) AS property_label,
                       COALESCE(u.unit_label, 'N/A') AS unit_label
                FROM rents r
                JOIN tenants t ON t.tenant_id = r.tenant_id
                JOIN properties p ON p.property_id = r.property_id
                LEFT JOIN property_units u ON u.id = r.unit_id
                LEFT JOIN (
                    SELECT tenant_id, name, phone, relationship
                    FROM tenant_guarantors
                    WHERE id IN (
                        SELECT MIN(id)
                        FROM tenant_guarantors
                        GROUP BY tenant_id
                    )
                ) tg ON tg.tenant_id = t.tenant_id
                LEFT JOIN (
                    SELECT tenant_id, name, phone, relationship
                    FROM tenant_next_of_kins
                    WHERE id IN (
                        SELECT MIN(id)
                        FROM tenant_next_of_kins
                        GROUP BY tenant_id
                    )
                ) nok ON nok.tenant_id = t.tenant_id
                WHERE r.rent_id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function getPaymentHistory(int $rentId): array
    {
        $stmt = $this->conn->prepare("SELECT
            payment_id,
            amount,
            payment_date,
            payment_method,
            receipt_no,
            status,
            paid_by_tenant_id
            FROM rent_payments
            WHERE rent_id = ?
            ORDER BY payment_date ASC, payment_id ASC");
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $runningTotal = 0;
        foreach ($payments as &$payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            $runningTotal += $amount;
            $payment['running_total'] = $runningTotal;
        }

        return $payments;
    }

    public function getPaymentSummary(int $rentId): array
    {
        $stmt = $this->conn->prepare("SELECT
            COUNT(*) as payment_count,
            SUM(amount) as total_paid,
            MAX(payment_date) as last_payment_date
            FROM rent_payments
            WHERE rent_id = ?");
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: [];
    }

    public function recordPayment(int $rentId, int $tenantId, float $amount, string $method = 'Cash'): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $before = $this->getDebtByRentId($rentId);
        $this->conn->begin_transaction();

        try {
            $receiptNo = 'RCP-' . date('Ymd') . '-' . $rentId . '-' . time();
            $paymentDate = date('Y-m-d');
            $paymentType = 'Rent';

            $ins = $this->conn->prepare("INSERT INTO rent_payments
                (rent_id, amount, payment_date, payment_method, payment_type, receipt_no, status, paid_by_tenant_id)
                VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)");
            $ins->bind_param('idssssi', $rentId, $amount, $paymentDate, $method, $paymentType, $receiptNo, $tenantId);
            $ins->execute();
            $paymentId = (int) $this->conn->insert_id;

            $upd = $this->conn->prepare("UPDATE rents
                SET total_pay = COALESCE(total_pay, 0) + ?,
                    balance_due = GREATEST(COALESCE(balance_due, 0) - ?, 0)
                WHERE rent_id = ?");
            $upd->bind_param('ddi', $amount, $amount, $rentId);
            $upd->execute();

            $this->conn->commit();

            $after = $this->getDebtByRentId($rentId);
            AuditService::instance()->record('payment.recorded', $paymentId, 'payment', [
                'rent_id' => $rentId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'method' => $method,
                'receipt_no' => $receiptNo,
                'status' => 'completed',
            ], 'Debt payment recorded');

            AuditService::instance()->mutate('rent.balance.updated', $rentId, 'rent', [
                'total_pay' => (float) ($before['total_pay'] ?? 0),
                'balance_due' => (float) ($before['balance_due'] ?? 0),
            ], [
                'total_pay' => (float) ($after['total_pay'] ?? 0),
                'balance_due' => (float) ($after['balance_due'] ?? 0),
            ], 'Balance updated after payment');

            return true;

        } catch (Throwable $e) {
            $this->conn->rollback();
            AuditService::instance()->failed('payment.recorded', 'Payment recording failed', [
                'entity_type' => 'payment',
                'rent_id' => $rentId,
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            error_log('Payment recording failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getTenantById(int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT
            t.tenant_id,
            t.full_name,
            t.email,
            t.phone,
            t.phone_raw,
            t.address,
            t.occupation,
            tg.name AS guarantor_name,
            tg.phone AS guarantor_phone,
            nok.name AS nok_name,
            nok.phone AS nok_phone
            FROM tenants t
            LEFT JOIN (
                SELECT tenant_id, name, phone
                FROM tenant_guarantors
                WHERE id IN (
                    SELECT MIN(id)
                    FROM tenant_guarantors
                    GROUP BY tenant_id
                )
            ) tg ON tg.tenant_id = t.tenant_id
            LEFT JOIN (
                SELECT tenant_id, name, phone
                FROM tenant_next_of_kins
                WHERE id IN (
                    SELECT MIN(id)
                    FROM tenant_next_of_kins
                    GROUP BY tenant_id
                )
            ) nok ON nok.tenant_id = t.tenant_id
            WHERE t.tenant_id = ?
            LIMIT 1");
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }
}
