<?php
declare(strict_types=1);

// Boot application runtime (config, session hardening, DB connection, autoloading).
require_once __DIR__ . '/../app/bootstrap.php';
// Delegate all login behavior to controller layer (thin entrypoint pattern).
require_once APP_ROOT . '/controllers/AuthController.php';

$controller = new AuthController();

// POST = attempt login, GET = render login page.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->login();
} else {
    $controller->showLogin();
}
