<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/RentAlertRepository.php';

class AlertService
{
    public static function loadHeaderAlerts(int $limit = 10): array
    {
        try {
            $repo = new RentAlertRepository();
            $alerts = $repo->getActiveAlertsByPriority($limit);
            $counts = [
                'critical' => $repo->countByPriority('critical'),
                'warning' => $repo->countByPriority('warning'),
                'info' => $repo->countByPriority('info'),
                'total_unresolved' => $repo->countUnresolved(),
            ];

            return [
                'alerts' => $alerts,
                'counts' => $counts,
            ];
        } catch (Throwable $e) {
            error_log('AlertService::loadHeaderAlerts error: ' . $e->getMessage());

            return [
                'alerts' => [],
                'counts' => [
                    'critical' => 0,
                    'warning' => 0,
                    'info' => 0,
                    'total_unresolved' => 0,
                ],
            ];
        }
    }
}
