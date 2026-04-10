<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';
require_once APP_ROOT . '/workers/send_mail_adapter.php';

class EmailService
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = getConnection();
    }

    public function queueSecurityEmail(string $to, string $subject, string $bodyHtml, string $type): bool
    {
        $stmt = $this->conn->prepare('INSERT INTO email_retry_queue
            (recipient, subject, body, category, attempt_count, next_attempt_at, created_at)
            VALUES (?, ?, ?, ?, 0, NOW(), NOW())');

        $plainBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $bodyHtml));
        $stmt->bind_param('ssss', $to, $subject, $plainBody, $type);
        if (!$stmt->execute()) {
            return false;
        }

        $this->sendImmediate($to, $subject, $bodyHtml, $type);
        return true;
    }

    private function sendImmediate(string $to, string $subject, string $bodyHtml, string $type): void
    {
        try {
            send_documents_to_parties([
                'email' => $to,
                'subject' => $subject,
                'body' => $bodyHtml,
                'attachments' => [],
                'recipient_type' => $type,
                'category' => 'security',
            ]);
        } catch (Throwable $e) {
            error_log('Email immediate send failed: ' . $e->getMessage());
        }
    }

    public function sendLockoutAlertToUser(array $user, int $attempts, int $minutesRemaining): void
    {
        $subject = 'Security Alert: Account Temporarily Locked';
        $body = '<p>Hello ' . htmlspecialchars($user['username'] ?? 'user') . ',</p>'
            . '<p>We detected ' . $attempts . ' failed login attempts.</p>'
            . '<p>Your account will auto-unlock in ' . $minutesRemaining . ' minutes, or use OTP reset.</p>';

        if (!empty($user['email'])) {
            $this->queueSecurityEmail((string) $user['email'], $subject, $body, 'lockout_user');
        }
    }

    public function sendLockoutAlertToAdmins(array $user, int $attempts, array $admins): void
    {
        $subject = 'SECURITY ALERT: Account Lockout - ' . ($user['username'] ?? 'unknown');
        $body = '<p>Locked account: ' . htmlspecialchars((string) ($user['username'] ?? 'unknown')) . '</p>'
            . '<p>Attempts: ' . $attempts . '</p>'
            . '<p><a href="' . BASE_URL . '/public/admin/unlock-account.php">Unlock Account</a></p>';

        foreach ($admins as $admin) {
            if (!empty($admin['email'])) {
                $this->queueSecurityEmail((string) $admin['email'], $subject, $body, 'lockout_admin');
            }
        }
    }

    public function sendOtpChallengeToUser(array $user, string $otpCode): void
    {
        if (empty($user['email'])) {
            return;
        }

        $subject = 'Verify Login Attempt - OTP';
        $body = '<p>Your verification code is <strong>' . htmlspecialchars($otpCode) . '</strong>.</p>'
            . '<p>This code expires in 10 minutes.</p>';
        $this->queueSecurityEmail((string) $user['email'], $subject, $body, 'otp_verification');
    }
}
