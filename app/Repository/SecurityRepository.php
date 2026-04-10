<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class SecurityRepository
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function getRecentAttempts(string $ip, int $minutes = 15): int
    {
        // IP-level guard used by controller pre-check.
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM portal_login_attempts
            WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE) AND success = 0');
        $stmt->bind_param('si', $ip, $minutes);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }

    public function recordAttempt(string $username, string $ip, bool $success): void
    {
        $successInt = $success ? 1 : 0;
        $stmt = $this->conn->prepare('INSERT INTO portal_login_attempts (username, ip, success, attempted_at, cleared)
            VALUES (?, ?, ?, NOW(), 0)');
        $stmt->bind_param('ssi', $username, $ip, $successInt);
        $stmt->execute();
    }

    public function clearAttempts(string $username): void
    {
        $stmt = $this->conn->prepare('UPDATE portal_login_attempts SET cleared = 1
            WHERE username = ? AND success = 0 AND cleared = 0');
        $stmt->bind_param('s', $username);
        $stmt->execute();
    }

    public function getFailedAttempts(string $username): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS count FROM portal_login_attempts
            WHERE username = ? AND success = 0 AND cleared = 0
            AND attempted_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['count'] ?? 0);
    }

    public function getLastAttemptTime(string $username): int
    {
        $stmt = $this->conn->prepare('SELECT UNIX_TIMESTAMP(MAX(attempted_at)) AS last_time FROM portal_login_attempts
            WHERE username = ? AND success = 0 AND cleared = 0');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['last_time'] ?? 0);
    }

    public function wasLockoutNotified(string $username): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM security_events WHERE username = ? AND event_type = 'account_locked'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function logSecurityEvent(array $data): void
    {
        $details = json_encode($data['details'] ?? [], JSON_UNESCAPED_SLASHES);
        $email = $data['email'] ?? '';
        $agent = $data['user_agent'] ?? '';
        $stmt = $this->conn->prepare('INSERT INTO security_events
            (event_type, username, email, ip_address, user_agent, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssssss', $data['event_type'], $data['username'], $email, $data['ip_address'], $agent, $details);
        $stmt->execute();
    }

    public function recordLoginTelemetry(array $data): void
    {
        // Comprehensive telemetry used for audit/risk analytics.
        $stmt = $this->conn->prepare('INSERT INTO login_telemetry
            (username,user_id,level,success,ip_address,user_agent,device_fingerprint,device_type,browser,os,portal,location_country,location_city,isp,session_id,failure_reason,attempted_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');

        $stmt->bind_param(
            'sisissssssssssss',
            $data['username'],
            $data['user_id'],
            $data['level'],
            $data['success'],
            $data['ip_address'],
            $data['user_agent'],
            $data['device_fingerprint'],
            $data['device_type'],
            $data['browser'],
            $data['os'],
            $data['portal'],
            $data['location_country'],
            $data['location_city'],
            $data['isp'],
            $data['session_id'],
            $data['failure_reason']
        );
        $stmt->execute();
    }

    public function createPasswordResetOtp(int $userId, string $otp, int $ttlMinutes = 10): void
    {
        // OTP record supports identity verification path during lockout.
        $stmt = $this->conn->prepare('INSERT INTO password_reset_otps (user_id, otp_code, expires_at, created_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())');
        $stmt->bind_param('isi', $userId, $otp, $ttlMinutes);
        $stmt->execute();
    }

    public function checkImpossibleTravel(int $userId, string $currentIp): ?array
    {
        $stmt = $this->conn->prepare("SELECT ip_address, created_at FROM portal_access_logs
            WHERE entity_id = ? AND action = 'login' ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $last = $stmt->get_result()->fetch_assoc();

        if (!$last || $last['ip_address'] === $currentIp) {
            return null;
        }

        $diff = time() - strtotime((string) $last['created_at']);
        if ($diff < 7200) {
            return [
                'previous_ip' => $last['ip_address'],
                'previous_time' => $last['created_at'],
                'current_ip' => $currentIp,
                'time_diff_minutes' => (int) round($diff / 60),
            ];
        }

        return null;
    }

    public function setUserLockState(int $targetUserId, bool $lock): void
    {
        // Soft lock/unlock by toggling account status.
        $status = $lock ? 'inactive' : 'active';
        $stmt = $this->conn->prepare('UPDATE portal_accounts SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $targetUserId);
        $stmt->execute();
    }

    public function getLockedAccounts(): array
    {
        $stmt = $this->conn->prepare("SELECT username, COUNT(*) AS failed_attempts, MAX(attempted_at) AS last_attempt
            FROM portal_login_attempts
            WHERE success = 0 AND cleared = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            GROUP BY username
            HAVING failed_attempts >= 5
            ORDER BY last_attempt DESC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLockedAccountsCount(): int
    {
        return count($this->getLockedAccounts());
    }
}
