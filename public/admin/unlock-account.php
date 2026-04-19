<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once APP_ROOT . '/controllers/UnlockAccountController.php';

$controller = new UnlockAccountController();
$controller->index();
