<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once APP_ROOT . '/services/DebtService.php';

header('Content-Type: application/json; charset=utf-8');
$service = new DebtService();

echo json_encode([
    'success' => true,
    'data' => $service->getSummary(),
    'timestamp' => date('c'),
]);
