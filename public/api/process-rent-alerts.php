<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once APP_ROOT . '/workers/RentAlertWorker.php';

header('Content-Type: application/json; charset=utf-8');

$secret = (string) ($_GET['key'] ?? '');
$expected = (string) ($_ENV['CRON_SECRET'] ?? '');

if ($expected === '' || !hash_equals($expected, $secret)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$worker = new RentAlertWorker();
$count = $worker->processDailyAlerts();

echo json_encode(['status' => 'success', 'processed' => $count, 'processed_at' => date('c')]);
