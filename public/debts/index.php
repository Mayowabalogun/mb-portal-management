<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once APP_ROOT . '/security/RoleGuard.php';
require_once APP_ROOT . '/controllers/DebtsController.php';

RoleGuard::requireRole(['staff', 'Manager', 'admin', 'super_admin', 'Super_Admin (Owner)']);

$controller = new DebtsController();
$action = $_GET['action'] ?? 'index';
if ($action === 'history') {
    $controller->history();
} elseif ($action === 'make-payment') {
    $controller->makePayment();
} else {
    $controller->index();
}
