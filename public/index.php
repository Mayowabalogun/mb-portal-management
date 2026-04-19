<?php
declare(strict_types=1);

// Runtime error policy: log errors, avoid rendering stack traces to end users.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Boot core constants + autoload registration.
require_once __DIR__ . '/../app/bootstrap.php';

// Route landing-page request directly to HomeController.
require_once APP_ROOT . '/controllers/HomeController.php';
$controller = new HomeController();
$controller->index();
