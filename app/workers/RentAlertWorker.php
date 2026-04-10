<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_ROOT . '/services/RentAlertService.php';

class RentAlertWorker
{
    private RentAlertService $service;

    public function __construct()
    {
        $this->service = new RentAlertService();
    }

    public function processDailyAlerts(): int
    {
        return $this->service->processDailyAlerts();
    }
}

if (PHP_SAPI === 'cli') {
    $worker = new RentAlertWorker();
    $processed = $worker->processDailyAlerts();
    echo 'processed=' . $processed . PHP_EOL;
}
