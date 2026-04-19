<?php
declare(strict_types=1);

/**
 * Global Configuration File
 *
 * Centralizes environment, paths, URLs, DB constants, and runtime settings.
 */

// 1) ENVIRONMENT SETTINGS
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'development');
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Africa/Lagos');

// 2) APPLICATION ROOTS & PATHS
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}
if (!defined('APP_ROOT')) {
    define('APP_ROOT', ROOT_PATH . '/app');
}
if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', ROOT_PATH . '/public');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', APP_ROOT . '/config');
}
if (!defined('CONNECTIONS_PATH')) {
    define('CONNECTIONS_PATH', APP_ROOT . '/connections');
}
if (!defined('MODULES_PATH')) {
    define('MODULES_PATH', APP_ROOT . '/modules');
}
if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', APP_ROOT . '/includes');
}
if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', APP_ROOT . '/logs');
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', PUBLIC_ROOT . '/assets');
}
if (!defined('IMAGES_PATH')) {
    define('IMAGES_PATH', PUBLIC_ROOT . '/images');
}
if (!defined('VENDORS_PATH')) {
    define('VENDORS_PATH', APP_ROOT . '/vendors');
}

// 3) BASE URL
if (!defined('BASE_URL')) {
    if (!empty($_ENV['APP_URL'])) {
        $baseUrl = rtrim((string) $_ENV['APP_URL'], '/');
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
    } else {
        $baseUrl = 'http://localhost';
    }

    define('BASE_URL', $baseUrl);
}

// 4) DATABASE CONFIGURATION
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'mb_portal');
}
if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) ($_ENV['DB_PORT'] ?? 3306));
}

if (!defined('AUTH_DB_HOST')) {
    define('AUTH_DB_HOST', $_ENV['AUTH_DB_HOST'] ?? DB_HOST);
}
if (!defined('AUTH_DB_NAME')) {
    define('AUTH_DB_NAME', $_ENV['AUTH_DB_NAME'] ?? '');
}
if (!defined('AUTH_DB_USER')) {
    define('AUTH_DB_USER', $_ENV['AUTH_DB_USER'] ?? DB_USER);
}
if (!defined('AUTH_DB_PASS')) {
    define('AUTH_DB_PASS', $_ENV['AUTH_DB_PASS'] ?? DB_PASS);
}
if (!defined('AUTH_DB_PORT')) {
    define('AUTH_DB_PORT', (int) ($_ENV['AUTH_DB_PORT'] ?? DB_PORT));
}

// 5) ERROR REPORTING
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// 6) SECURITY SETTINGS
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', '_csrf_token');
}
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 3600);
}

// 7) HELPER PATH BUILDERS
if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return APP_ROOT . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return PUBLIC_ROOT . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        return BASE_URL . '/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('image_url')) {
    function image_url(string $path = ''): string
    {
        return BASE_URL . '/public/images/' . ltrim($path, '/');
    }
}

if (!function_exists('vendor_path')) {
    function vendor_path(string $path = ''): string
    {
        return VENDORS_PATH . '/' . ltrim($path, '/');
    }
}
