<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once APP_ROOT . '/services/DashboardService.php';
require_once APP_ROOT . '/services/RentAlertService.php';

header('Content-Type: application/json; charset=utf-8');

$service = new DashboardService();
$rentAlertService = new RentAlertService();
$alerts = $rentAlertService->hasAlertStorage()
    ? $rentAlertService->getTickerAlerts(10)
    : $service->getTickerAlerts(10);

$payload = array_map(static function (array $alert): array {
    if (!empty($alert['message'])) {
        return [
            'type' => (string) ($alert['type'] ?? 'Alert'),
            'message' => (string) $alert['message'],
            'priority' => (string) ($alert['priority'] ?? 'info'),
            'alert_type' => (string) ($alert['alert_type'] ?? 'due_soon'),
        ];
    }

    $tenant = (string) ($alert['tenant'] ?? 'Unknown Tenant');
    $property = (string) ($alert['property'] ?? 'Unknown Property');
    $amount = number_format((float) ($alert['amount'] ?? 0), 2);

    return [
        'type' => (string) ($alert['type'] ?? 'Alert'),
        'message' => $tenant . ' — ' . $property . ' owes ₦' . $amount,
        'priority' => (string) ($alert['priority'] ?? 'info'),
        'alert_type' => (string) ($alert['alert_type'] ?? 'due_soon'),
    ];
}, $alerts);

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
