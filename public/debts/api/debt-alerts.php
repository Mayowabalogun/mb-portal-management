<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/bootstrap.php';
require_once APP_ROOT . '/services/DebtService.php';

header('Content-Type: application/json; charset=utf-8');
$service = new DebtService();

$summary = $service->getSummary();
$alerts = $service->getHeaderAlerts();

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'counts' => $alerts['counts'] ?? [],
    'alerts' => $alerts['alerts'] ?? [],
]);
