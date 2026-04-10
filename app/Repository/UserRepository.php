<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class UserRepository
{
    // Shared DB connection accessor.
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function getAdmins(): array
    {
        // Super Admin + Manager recipients for security notifications.
        $stmt = $this->conn->prepare("SELECT DISTINCT pa.email, pa.username, r.name AS level
            FROM portal_accounts pa
            INNER JOIN portal_account_roles par ON pa.id = par.portal_account_id
            INNER JOIN roles r ON par.role_id = r.id
            WHERE r.name IN ('Super_Admin (Owner)', 'Manager')
              AND pa.status = 'active'");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findByUsername(string $username): ?array
    {
        // Unified credential lookup supports either username or email.
        $stmt = $this->conn->prepare("SELECT pa.id as user_id, pa.username, pa.email, pa.password_hash,
                pa.status as is_active, r.name as level, t.tenant_id, l.landlord_id, u.id as staff_id
            FROM portal_accounts pa
            INNER JOIN portal_account_roles par ON par.portal_account_id = pa.id
            INNER JOIN roles r ON r.id = par.role_id
            LEFT JOIN tenants t ON pa.id = t.portal_account_id
            LEFT JOIN landlords l ON pa.id = l.portal_account_id
            LEFT JOIN users u ON pa.id = u.portal_account_id
            WHERE (pa.username = ? OR pa.email = ?) LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function storeRememberToken(int $userId, string $token): void
    {
        $stmt = $this->conn->prepare('INSERT INTO portal_account_tokens (portal_account_id, token, expires_at, created_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())');
        $stmt->bind_param('is', $userId, $token);
        $stmt->execute();
    }

    public function logAccess(string $portalType, int $entityId, string $action): void
    {
        // Append audit record for login/logout traceability.
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
        $stmt = $this->conn->prepare('INSERT INTO portal_access_logs
            (portal_type, entity_id, action, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('sisss', $portalType, $entityId, $action, $ip, $agent);
        $stmt->execute();
    }
}
