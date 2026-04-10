<?php
declare(strict_types=1);

require_once APP_ROOT . '/security/RoleGuard.php';
require_once APP_ROOT . '/Repository/SecurityRepository.php';
require_once APP_ROOT . '/utils/flash.php';

class UnlockAccountController
{
    private SecurityRepository $securityRepo;
    private array $viewData = [];
    private string $userLevel;

    public function __construct()
    {
        RoleGuard::requireRole(['Super_Admin (Owner)', 'Manager']);
        $this->securityRepo = new SecurityRepository();
        $this->userLevel = $_SESSION['level'] ?? 'Manager';
        $this->initializeCsrfToken();
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUnlockRequest();
        }

        $this->prepareViewData();
        $this->renderView();
    }

    private function handleUnlockRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $username = trim($_POST['username'] ?? '');

        if (!$this->validateCsrf($token)) {
            add_flash('error', 'Invalid security token.');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($username === '') {
            add_flash('error', 'Please enter a username.');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $lockedAccounts = method_exists($this->securityRepo, 'getLockedAccounts')
            ? $this->securityRepo->getLockedAccounts()
            : [];

        $isLocked = false;
        foreach ($lockedAccounts as $account) {
            if (($account['username'] ?? '') === $username) {
                $isLocked = true;
                break;
            }
        }

        if (!$isLocked) {
            add_flash('warning', "Account '{$username}' is not currently locked.");
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $this->securityRepo->clearAttempts($username);
        $this->securityRepo->logSecurityEvent([
            'event_type' => 'manual_unlock',
            'username' => $username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => [
                'unlocked_by' => $_SESSION['username'] ?? 'unknown',
                'unlocked_by_id' => $_SESSION['user_id'] ?? null,
                'unlocked_by_level' => $this->userLevel,
            ],
        ]);

        add_flash('success', "Account '{$username}' has been unlocked successfully.");
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    private function prepareViewData(): void
    {
        $lockedAccounts = method_exists($this->securityRepo, 'getLockedAccounts')
            ? $this->securityRepo->getLockedAccounts()
            : [];

        $this->viewData = [
            'page_title' => 'Unlock Accounts | MB PropertyFinder',
            'csrf_token' => $_SESSION['csrf_token'],
            'locked_accounts' => $lockedAccounts,
            'current_user' => $_SESSION['username'] ?? 'Admin',
            'user_level' => $this->userLevel,
            'menu' => $this->getMenu(count($lockedAccounts)),
            'locked_count' => count($lockedAccounts),
        ];
    }

    private function getMenu(int $lockedCount): array
    {
        $base = BASE_URL;
        return [
            ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'url' => "{$base}/public/admin/admin-dashboard.php"],
            ['icon' => 'fa-lock', 'label' => 'Unlock Accounts', 'url' => "{$base}/public/admin/unlock-account.php", 'active' => true, 'highlight' => true, 'badge' => $lockedCount > 0 ? $lockedCount : null],
        ];
    }

    private function validateCsrf(string $token): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    private function initializeCsrfToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function renderView(): void
    {
        extract($this->viewData);
        require APP_ROOT . '/views/admin/unlock-account_view.php';
    }
}
