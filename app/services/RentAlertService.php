<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/RentAlertRepository.php';

class RentAlertService
{
    private RentAlertRepository $repo;

    public function __construct()
    {
        $this->repo = new RentAlertRepository();
    }

    public function getTickerAlerts(int $limit = 10): array
    {
        return $this->repo->getActiveAlertsByPriority($limit);
    }

    public function getAlertCounts(): array
    {
        return [
            'critical' => $this->repo->countByPriority('critical'),
            'warning' => $this->repo->countByPriority('warning'),
            'info' => $this->repo->countByPriority('info'),
            'total_unresolved' => $this->repo->countUnresolved(),
        ];
    }

    public function resolveAlert(int $leaseId, float $amountPaid): bool
    {
        $outstanding = $this->repo->getOutstandingAmount($leaseId);
        if ($outstanding <= 0 || $amountPaid < $outstanding) {
            return false;
        }

        return $this->repo->markResolved($leaseId);
    }

    public function processDailyAlerts(): int
    {
        return $this->repo->upsertComputedAlerts();
    }

    public function hasAlertStorage(): bool
    {
        return $this->repo->hasAlertsTable();
    }
}
