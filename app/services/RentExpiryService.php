<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/RentExpiryRepository.php';
require_once APP_ROOT . '/services/notifications/AlertService.php';

class RentExpiryService
{
    private RentExpiryRepository $repo;

    public function __construct()
    {
        $this->repo = new RentExpiryRepository();
    }

    public function getExpiredRents(?string $role = null, ?int $entityId = null): array
    {
        if ($this->isLandlord($role) && $entityId !== null) {
            return $this->repo->findExpiredRentsByLandlord($entityId);
        }

        return $this->repo->findExpiredRents();
    }

    public function countExpiredRents(?string $role = null, ?int $entityId = null): int
    {
        if ($this->isLandlord($role) && $entityId !== null) {
            return $this->repo->countExpiredByLandlord($entityId);
        }

        return $this->repo->countExpiredRents();
    }

    public function getExpirySummaryByType(): array
    {
        return $this->repo->getExpirySummaryByType();
    }

    public function getLeaseStateSummary(?string $role = null, ?int $entityId = null): array
    {
        $rows = $this->isLandlord($role) && $entityId !== null
            ? $this->repo->getLeaseStateSummary($entityId)
            : $this->repo->getLeaseStateSummary();

        $defaults = [
            'ACTIVE' => 0,
            'EXPIRING_SOON' => 0,
            'EXPIRED' => 0,
            'HOLDOVER' => 0,
            'VACATED' => 0,
        ];

        foreach ($rows as $row) {
            $state = strtoupper((string) ($row['lease_state'] ?? ''));
            if (array_key_exists($state, $defaults)) {
                $defaults[$state] = (int) ($row['total'] ?? 0);
            }
        }

        return $defaults;
    }

    public function vacateByExpiry(int $rentId, ?string $role = null, ?int $entityId = null): bool
    {
        if ($rentId <= 0) {
            return false;
        }

        $landlordId = $this->isLandlord($role) ? $entityId : null;
        $ownership = $this->repo->verifyRentOwnership($rentId, $landlordId);
        if (!$ownership) {
            return false;
        }

        return $this->repo->executeVacate(
            (int) $ownership['rent_id'],
            (int) $ownership['unit_id'],
            (int) $ownership['tenant_id']
        );
    }

    public function getHeaderAlerts(): array
    {
        return AlertService::loadHeaderAlerts();
    }

    private function isLandlord(?string $role): bool
    {
        return strtolower((string) $role) === 'landlord';
    }
}
