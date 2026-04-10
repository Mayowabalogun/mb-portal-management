<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('APP_ROOT')) {
    define('APP_ROOT', ROOT_PATH . '/app');
}
if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', ROOT_PATH . '/public');
}

require_once APP_ROOT . '/config/config.php';

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string) SESSION_TIMEOUT);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
    $_SESSION['last_regeneration'] = time();
} elseif (time() - (int) $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$_SESSION['last_activity'] = time();

require_once APP_ROOT . '/connections/db.php';
require_once APP_ROOT . '/services/AuditService.php';

$optionalHelpers = [
    APP_ROOT . '/utils/flash.php',
    APP_ROOT . '/helpers/audit_log.php',
];

foreach ($optionalHelpers as $helperFile) {
    if (is_file($helperFile)) {
        require_once $helperFile;
    }
}

spl_autoload_register(static function (string $class): void {
    $paths = [
        APP_ROOT . '/controllers/' . $class . '.php',
        APP_ROOT . '/services/' . $class . '.php',
        APP_ROOT . '/services/notifications/' . $class . '.php',
        APP_ROOT . '/Repository/' . $class . '.php',
        APP_ROOT . '/security/' . $class . '.php',
        APP_ROOT . '/modules/' . $class . '.php',
        APP_ROOT . '/modules/debts/' . $class . '.php',
        APP_ROOT . '/modules/home/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

// ======================================================
// Global Database Connection
// ======================================================
$conn = getConnection();

if (!$conn instanceof mysqli) {
    die('Database connection failed.');
}
