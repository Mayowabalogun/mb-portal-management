<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/DebtRepository.php';
require_once APP_ROOT . '/services/RentService.php';
require_once APP_ROOT . '/services/notifications/AlertService.php';

class DebtService
{
    private DebtRepository $repo;

    public function __construct()
    {
        $this->repo = new DebtRepository();
    }

    public function getDebtors(int $limit = 25, int $offset = 0, string $category = 'all', string $search = ''): array
    {
        $rows = $this->repo->getDebtors($limit, $offset, $category, $search);

        foreach ($rows as &$row) {
            $days = (int) ($row['days_overdue'] ?? 0);
            $row['category'] = $days > 365 ? 'LEGAL' : ($days > 90 ? 'CRITICAL' : ($days > 30 ? 'HARD' : ($days > 0 ? 'SOFT' : 'OUTSTANDING')));
        }

        return $rows;
    }

    public function getSummary(): array
    {
        return $this->repo->getDebtSummary();
    }

    public function countDebtors(string $category = 'all', string $search = ''): int
    {
        return $this->repo->countDebtors($category, $search);
    }

    public function getDebtByRentId(int $rentId): ?array
    {
        return $this->repo->getDebtByRentId($rentId);
    }

    public function getPaymentHistory(int $rentId): array
    {
        return $this->repo->getPaymentHistory($rentId);
    }

    public function recordPayment(int $rentId, int $tenantId, float $amount, string $method = 'Cash'): bool
    {
        $service = new RentService();
        return $service->processPayment($rentId, $tenantId, $amount, $method, 'Rent');
    }

    public function getHeaderAlerts(): array
    {
        return AlertService::loadHeaderAlerts();
    }
}
