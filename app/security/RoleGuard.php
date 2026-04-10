<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/AuthService.php';

class RoleGuard
{
    private static ?AuthService $auth = null;

    private static array $roleHierarchy = [
        'Super_Admin (Owner)' => ['Super_Admin (Owner)', 'Manager', 'staff', 'landlord', 'tenant', 'vendor', 'partner'],
        'Manager' => ['Manager', 'staff', 'landlord', 'tenant', 'vendor', 'partner'],
        'staff' => ['staff', 'tenant'],
        'landlord' => ['landlord'],
        'tenant' => ['tenant'],
        'vendor' => ['vendor'],
        'partner' => ['partner'],
    ];

    private static function auth(): AuthService
    {
        if (self::$auth === null) {
            self::$auth = new AuthService();
        }

        return self::$auth;
    }

    public static function requireLogin(): void
    {
        if (!self::auth()->isAuthenticated()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: ' . BASE_URL . '/public/login.php?redirect=' . $redirect);
            exit;
        }
    }

    public static function requireRole(array $allowedRoles): void
    {
        self::requireLogin();
        $level = $_SESSION['level'] ?? null;
        $roles = self::$roleHierarchy[$level] ?? [$level];

        foreach ($allowedRoles as $role) {
            if (in_array($role, $roles, true)) {
                return;
            }
        }

        self::denyAccess('Insufficient privileges.');
    }

    public static function requireAdmin(): void
    {
        self::requireRole(['Super_Admin (Owner)', 'Manager', 'staff']);
    }

    private static function denyAccess(string $reason): void
    {
        http_response_code(403);
        echo htmlspecialchars($reason);
        exit;
    }
}
