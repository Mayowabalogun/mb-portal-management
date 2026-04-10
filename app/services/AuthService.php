<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/UserRepository.php';
require_once APP_ROOT . '/Repository/SecurityRepository.php';
require_once APP_ROOT . '/services/EmailService.php';

class AuthService
{
    // Data access dependencies.
    private UserRepository $userRepo;
    private SecurityRepository $securityRepo;
    private EmailService $emailService;

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 1800; // 30 minutes

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->securityRepo = new SecurityRepository();
        $this->emailService = new EmailService();
    }

    public function authenticateWithRestriction(string $username, string $password, array $allowedLevels, string $portal = 'main'): array
    {
        // Run base authentication first (credentials + lockout + telemetry).
        $result = $this->performAuthentication($username, $password, $portal);

        if (!$result['success']) {
            return $result;
        }

        // Enforce portal-role boundary (same login page, scoped access).
        if (!in_array($result['level'], $allowedLevels, true)) {
            return [
                'success' => false,
                'message' => 'You do not have access to this portal. Please use the correct login page.',
            ];
        }

        return $result;
    }

    private function performAuthentication(string $username, string $password, string $portal = 'main'): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = $this->userRepo->findByUsername($username);

        // Lockout policy: auto-unlock after 30 minutes, with notification + OTP fallback.
        if ($this->isAccountLocked($username)) {
            $minutes = (int) ceil(self::LOCKOUT_TIME / 60);
            if ($user !== null) {
                $admins = $this->userRepo->getAdmins();
                $this->emailService->sendLockoutAlertToUser($user, self::MAX_ATTEMPTS, $minutes);
                $this->emailService->sendLockoutAlertToAdmins($user, self::MAX_ATTEMPTS, $admins);
                $otp = (string) random_int(100000, 999999);
                $this->securityRepo->createPasswordResetOtp((int) $user['user_id'], $otp, 10);
                $this->emailService->sendOtpChallengeToUser($user, $otp);
            }

            return [
                'success' => false,
                'message' => 'Account locked for 30 minutes. Use OTP/identity verification or wait for auto-unlock.',
            ];
        }

        $hash = $user['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuv';
        $isValid = password_verify($password, $hash);

        // Invalid credentials/account status => store failed attempt + telemetry.
        if (!$user || !$isValid || ($user['is_active'] ?? 'inactive') !== 'active') {
            $this->securityRepo->recordAttempt($username, $ip, false);
            $this->recordTelemetry($username, $user, false, $portal, 'invalid_credentials');
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Successful login => clear lockout attempts and record success telemetry.
        $this->securityRepo->clearAttempts($username);
        $this->recordTelemetry($username, $user, true, $portal, null);

        $userId = (int) $user['user_id'];
        // Impossible-travel signal can notify admin operators for extra review.
        $travel = $this->securityRepo->checkImpossibleTravel($userId, $ip);
        if ($travel !== null) {
            $admins = $this->userRepo->getAdmins();
            $this->emailService->sendLockoutAlertToAdmins($user, 1, $admins);
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'username' => (string) $user['username'],
            'level' => (string) $user['level'],
            'entity_id' => $this->determineEntityId($user),
            'permissions' => $this->getPermissions((string) $user['level']),
            'email' => (string) ($user['email'] ?? ''),
        ];
    }

    private function recordTelemetry(string $username, ?array $user, bool $success, string $portal, ?string $reason): void
    {
        $this->securityRepo->recordLoginTelemetry([
            'username' => $username,
            'user_id' => $success ? (int) ($user['user_id'] ?? 0) : (int) ($user['user_id'] ?? 0),
            'level' => (string) ($user['level'] ?? ''),
            'success' => $success ? 1 : 0,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'device_fingerprint' => hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['HTTP_ACCEPT'] ?? '')),
            'device_type' => 'Desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'portal' => $portal,
            'location_country' => 'Unknown',
            'location_city' => 'Unknown',
            'isp' => 'Unknown',
            'session_id' => session_id() ?: '',
            'failure_reason' => $reason,
        ]);
    }

    public function createSession(array $authResult, bool $remember = false): void
    {
        // Regenerate session id to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) ($authResult['user_id'] ?? 0);
        $_SESSION['username'] = $authResult['username'] ?? '';
        $_SESSION['level'] = $authResult['level'] ?? 'guest';
        $_SESSION['entity_id'] = $authResult['entity_id'] ?? null;
        $_SESSION['permissions'] = $authResult['permissions'] ?? [];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        if ($remember && $_SESSION['user_id'] > 0) {
            // Long-lived token for convenience login.
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, [
                'expires' => time() + 30 * 24 * 60 * 60,
                'path' => '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $this->userRepo->storeRememberToken((int) $_SESSION['user_id'], $token);
        }
    }

    public function isAuthenticated(): bool
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['level'])) {
            return false;
        }
        return true;
    }

    public function getCurrentLevel(): ?string
    {
        return $_SESSION['level'] ?? null;
    }

    public function getCurrentUser(): array
    {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'level' => $_SESSION['level'] ?? '',
            'entity_id' => $_SESSION['entity_id'] ?? null,
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function checkRateLimit(string $ip): bool
    {
        return $this->securityRepo->getRecentAttempts($ip) < self::MAX_ATTEMPTS;
    }

    public function recordAttempt(string $username, string $ip, bool $success): void
    {
        $this->securityRepo->recordAttempt($username, $ip, $success);
    }

    public function logAccess(string $level, ?int $entityId, string $action): void
    {
        $this->userRepo->logAccess($level, $entityId ?? 0, $action);
    }

    public function adminSetUserLock(int $actorUserId, string $actorRole, int $targetUserId, string $targetRole, bool $lock): bool
    {
        // Explicit rule: manager cannot lock Super Admin (Owner).
        if ($actorRole === 'Manager' && $targetRole === 'Super_Admin (Owner)') {
            return false;
        }

        if (!in_array($actorRole, ['Super_Admin (Owner)', 'Manager'], true)) {
            return false;
        }

        $this->securityRepo->setUserLockState($targetUserId, $lock);
        return true;
    }

    private function determineEntityId(array $user): ?int
    {
        $level = $user['level'] ?? '';
        if (in_array($level, ['Super_Admin (Owner)', 'Manager', 'staff'], true)) {
            return null;
        }

        $map = [
            'tenant' => 'tenant_id',
            'landlord' => 'landlord_id',
        ];

        $field = $map[$level] ?? null;
        return $field !== null && !empty($user[$field]) ? (int) $user[$field] : null;
    }

    private function getPermissions(string $level): array
    {
        $fallbackPermissions = [
            'tenant' => ['view_lease', 'pay_rent', 'request_maintenance'],
            'landlord' => ['manage_properties', 'manage_tenants', 'view_financials'],
            'vendor' => ['view_work_orders', 'update_status', 'submit_invoices'],
            'partner' => ['view_collaborations', 'manage_projects'],
            'staff' => ['view_reports', 'manage_users'],
            'Super_Admin (Owner)' => ['all'],
            'Manager' => ['all'],
        ];

        return $fallbackPermissions[$level] ?? [];
    }

    private function isAccountLocked(string $username): bool
    {
        $attempts = $this->securityRepo->getFailedAttempts($username);
        if ($attempts < self::MAX_ATTEMPTS) {
            return false;
        }

        $lastAttempt = $this->securityRepo->getLastAttemptTime($username);
        return (time() - $lastAttempt) < self::LOCKOUT_TIME;
    }
}
