<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_ROOT . '/security/RoleGuard.php';
require_once APP_ROOT . '/controllers/ExpiredRentController.php';

RoleGuard::requireRole(['landlord', 'staff', 'Manager', 'admin', 'super_admin', 'Super_Admin (Owner)']);

$controller = new ExpiredRentController();
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'index');

if ($action === 'vacate') {
    $controller->vacate();
} else {
    $controller->index();
}
