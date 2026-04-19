<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class PaymentRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? getConnection();
    }

    public function insert(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO rent_payments (rent_id, amount, payment_date, payment_method, payment_type, status, paid_by_tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
            'idssssi',
            $data['rent_id'],
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'],
            $data['payment_type'],
            $data['status'],
            $data['paid_by_tenant_id']
        );
        if (!$stmt->execute()) {
            throw new RuntimeException('Payment insert failed: ' . $stmt->error);
        }

        $paymentId = (int) $this->db->insert_id;
        $stmt->close();

        $receiptNo = $this->generateReceiptNumber($paymentId);
        $this->updateReceiptNumber($paymentId, $receiptNo);
        return $paymentId;
    }

    public function updateReceiptNumber(int $paymentId, string $receiptNo): bool
    {
        $stmt = $this->db->prepare('UPDATE rent_payments SET receipt_no = ? WHERE payment_id = ?');
        $stmt->bind_param('si', $receiptNo, $paymentId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function findByRentId(int $rentId): array
    {
        $stmt = $this->db->prepare('SELECT payment_id, payment_date, payment_type, amount, receipt_no, payment_method, status FROM rent_payments WHERE rent_id = ? ORDER BY payment_date ASC, payment_id ASC');
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $running = 0.0;
        foreach ($rows as &$row) {
            $row['amount'] = (float) $row['amount'];
            $running += $row['amount'];
            $row['cumulative_total'] = $running;
        }

        return $rows;
    }

    public function findTotalsByType(int $rentId): array
    {
        $stmt = $this->db->prepare('SELECT payment_type, SUM(amount) AS total FROM rent_payments WHERE rent_id = ? GROUP BY payment_type');
        $stmt->bind_param('i', $rentId);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[(string) $row['payment_type']] = (float) $row['total'];
        }
        $stmt->close();
        return $rows;
    }

    public function queueLeaseOfferJob(int $rentId, int $receiptId): bool
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO lease_offer_jobs (rent_id, receipt_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
        $stmt->bind_param('ii', $rentId, $receiptId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    private function generateReceiptNumber(int $paymentId): string
    {
        return 'MBR-' . date('Ymd') . '-' . str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT);
    }
}
