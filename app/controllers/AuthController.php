<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/AuthService.php';
require_once APP_ROOT . '/services/AuditService.php';
require_once APP_ROOT . '/utils/flash.php';

class AuthController
{
    // Service layer handles authentication, security rules and audit operations.
    private AuthService $authService;

    private const PORTAL_CONFIG = [
        'main' => [
            'allowed_levels' => ['tenant', 'landlord', 'vendor', 'partner', 'Super_Admin (Owner)', 'Manager', 'staff'],
            'redirect' => '/public/dashboard.php',
            'theme' => 'default',
            'title' => 'Sign In',
            'subtitle' => 'Access your account',
        ],
        'tenant' => [
            'allowed_levels' => ['tenant'],
            'redirect' => '/public/portal/tenants/dashboard.php',
            'theme' => 'tenant',
            'title' => 'Tenant Portal',
            'subtitle' => 'Access your lease, payments, and maintenance requests',
        ],
        'landlord' => [
            'allowed_levels' => ['landlord'],
            'redirect' => '/public/portal/landlords/dashboard.php',
            'theme' => 'landlord',
            'title' => 'Landlord Portal',
            'subtitle' => 'Manage your properties and tenants',
        ],
        'vendor' => [
            'allowed_levels' => ['vendor'],
            'redirect' => '/public/portal/vendors/dashboard.php',
            'theme' => 'vendor',
            'title' => 'Vendor Portal',
            'subtitle' => 'Manage work orders and invoices',
        ],
        'partner' => [
            'allowed_levels' => ['partner'],
            'redirect' => '/public/portal/partners/dashboard.php',
            'theme' => 'partner',
            'title' => 'Partner Portal',
            'subtitle' => 'View referrals and commission earnings',
        ],
        'admin' => [
            'allowed_levels' => ['Super_Admin (Owner)', 'Manager'],
            'redirect' => '/public/admin/admin-dashboard.php',
            'theme' => 'admin',
            'title' => 'Admin Portal',
            'subtitle' => 'System administration and management',
        ],
        'staff' => [
            'allowed_levels' => ['staff', 'Super_Admin (Owner)', 'Manager'],
            'redirect' => '/public/admin/staff/dashboard.php',
            'theme' => 'admin',
            'title' => 'Staff Portal',
            'subtitle' => 'Daily operations and tenant management',
        ],
    ];

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        // Portal selector controls theme + role restrictions using one unified login page.
        $portal = $_GET['portal'] ?? 'main';
        if (!isset(self::PORTAL_CONFIG[$portal])) {
            $portal = 'main';
        }

        $config = self::PORTAL_CONFIG[$portal];

        if ($this->authService->isAuthenticated()) {
            $currentLevel = $this->authService->getCurrentLevel();
            // Already authenticated and allowed for this portal? send user to portal dashboard.
            if ($currentLevel !== null && in_array($currentLevel, $config['allowed_levels'], true)) {
                $this->redirect($config['redirect']);
            }
        }

        $csrfToken = $this->generateCsrfToken();
        $error = $_SESSION['login_error'] ?? '';
        unset($_SESSION['login_error']);
        $redirect = $_GET['redirect'] ?? '';

        $this->renderLoginView($config, $portal, $csrfToken, $error, $redirect);
    }

    private function renderLoginView(array $config, string $portal, string $csrfToken, string $error, string $redirect = ''): void
    {
        $title = $config['title'];
        $subtitle = $config['subtitle'];
        $theme = $config['theme'];
        require APP_ROOT . '/views/auth/unified-login.php';
    }

    public function login(): void
    {
        $portal = $_POST['portal'] ?? 'main';
        $config = self::PORTAL_CONFIG[$portal] ?? self::PORTAL_CONFIG['main'];

        // IP rate limit check to reduce brute-force attempts.
        if (!$this->checkRateLimit()) {
            $_SESSION['login_error'] = 'Too many attempts. Please try again later.';
            $this->redirect('/public/login.php?portal=' . urlencode($portal));
        }

        // CSRF token validation for form integrity.
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $_SESSION['login_error'] = 'Invalid security token. Please refresh and try again.';
            $this->redirect('/public/login.php?portal=' . urlencode($portal));
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            $this->redirect('/public/login.php?portal=' . urlencode($portal));
        }

        $result = $this->authService->authenticateWithRestriction(
            $username,
            $password,
            $config['allowed_levels'],
            $portal
        );

        if (!$result['success']) {
            // Persist failed attempt telemetry for lockout/risk analysis.
            $this->recordAttempt($username);
            AuditService::instance()->failed('admin.login', $result['message'] ?? 'Invalid credentials', [
                'entity_type' => 'auth',
                'username' => $username,
                'portal' => $portal,
            ]);
            $_SESSION['login_error'] = $result['message'] ?? 'Invalid credentials.';
            $this->redirect('/public/login.php?portal=' . urlencode($portal));
        }

        $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
        // Create secure session + optional remember-token.
        $this->authService->createSession($result, $remember);
        $this->authService->logAccess($result['level'], $result['entity_id'], 'login');
        AuditService::instance()->portal('LOGIN_SUCCESS', $portal === 'main' ? 'admin' : $portal);

        add_flash('success', 'Welcome back, ' . ($result['username'] ?? 'user') . '!');

        $redirect = $_POST['redirect'] ?? '';
        // Allow only internal public routes for post-login redirects.
        if ($redirect !== '' && str_starts_with($redirect, '/public/')) {
            $this->redirect($redirect);
        }

        $this->redirect($this->determineRedirect((string) $result['level'], $portal));
    }

    public function logout(): void
    {
        if ($this->authService->isAuthenticated()) {
            $user = $this->authService->getCurrentUser();
            $this->authService->logAccess($user['level'] ?? 'unknown', $user['entity_id'] ?? 0, 'logout');
            AuditService::instance()->portal('LOGOUT', 'admin');
        }

        $this->authService->logout();
        add_flash('info', 'You have been logged out successfully.');
        $this->redirect('/public/login.php');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function checkRateLimit(): bool
    {
        return $this->authService->checkRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function recordAttempt(string $username): void
    {
        $this->authService->recordAttempt($username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', false);
    }

    private function determineRedirect(string $level, string $portal): string
    {
        if ($portal !== 'main' && isset(self::PORTAL_CONFIG[$portal])) {
            $config = self::PORTAL_CONFIG[$portal];
            if (in_array($level, $config['allowed_levels'], true)) {
                return $config['redirect'];
            }
        }

        $redirects = [
            'tenant' => '/public/portal/tenants/dashboard.php',
            'landlord' => '/public/portal/landlords/dashboard.php',
            'vendor' => '/public/portal/vendors/dashboard.php',
            'partner' => '/public/portal/partners/dashboard.php',
            'staff' => '/public/admin/staff/dashboard.php',
            'Super_Admin (Owner)' => '/public/admin/admin-dashboard.php',
            'Manager' => '/public/admin/admin-dashboard.php',
        ];

        return $redirects[$level] ?? '/public/dashboard.php';
    }
}
