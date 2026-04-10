<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once APP_ROOT . '/security/RoleGuard.php';
require_once APP_ROOT . '/controllers/DashboardController.php';

// Consistent role labels with auth subsystem.
RoleGuard::requireRole(['Super_Admin (Owner)', 'Manager', 'staff']);

$controller = new DashboardController();
$controller->index();
