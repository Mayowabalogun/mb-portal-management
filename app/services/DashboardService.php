<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/DashboardRepository.php';
require_once APP_ROOT . '/Repository/AuditRepository.php';

class DashboardService
{
    private DashboardRepository $repo;
    private AuditRepository $auditRepo;

    public function __construct()
    {
        $this->repo = new DashboardRepository();
        $this->auditRepo = new AuditRepository();
    }

    public function getDashboardStats(string $viewMode): array
    {
        return match ($viewMode) {
            'super_admin' => $this->getSuperAdminStats(),
            'admin' => $this->getAdminStats(),
            'manager' => $this->getManagerStats(),
            'project_manager' => $this->getProjectManagerStats(),
            default => $this->getStaffStats(),
        };
    }

    public function getSuperAdminStats(): array
    {
        return array_merge($this->getAdminStats(), [
            'total_users' => $this->repo->countActiveUsers(),
            'total_vendors' => $this->repo->countActiveVendors(),
            'total_partners' => $this->repo->countActivePartners(),
            'system_health' => $this->repo->getSystemHealthMetrics(),
        ]);
    }

    public function getAdminStats(): array
    {
        $totalUnits = $this->repo->countPropertyUnits();
        $occupiedUnits = $this->repo->countOccupiedUnits();
        $vacantUnits = $this->repo->countVacantUnits();

        return [
            'properties' => $this->repo->countProperties(),
            'active_properties' => $this->repo->countActiveProperties(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
            'vacancy_rate' => $totalUnits > 0 ? round(($vacantUnits / $totalUnits) * 100, 1) : 0,
            'landlords' => $this->repo->countActiveLandlords(),
            'tenants' => $this->repo->countActiveTenants(),
            'new_tenants_this_month' => $this->repo->countNewTenantsThisMonth(),
            'active_rents' => $this->repo->countRentByStatus('Active'),
            'expiring_rent' => $this->repo->countExpiringRents(),
            'due_rent' => $this->repo->countRentByStatus('Due'),
            'overdue_rent' => $this->repo->countRentByStatus('Overdue'),
            'monthly_revenue' => $this->repo->getMonthlyRevenue(),
            'total_collected' => $this->repo->getTotalCollectedRent(),
            'outstanding_rent' => $this->repo->getOutstandingRent(),
            'total_expenses' => $this->repo->getTotalExpenses(),
            'open_maintenance' => $this->repo->countOpenMaintenanceRequests(),
            'urgent_maintenance' => $this->repo->countMaintenanceByPriority('urgent'),
            'high_priority_maintenance' => $this->repo->countMaintenanceByPriority('high'),
            'recent_logins' => $this->repo->getRecentLogins(10),
        ];
    }

    public function getManagerStats(): array
    {
        return array_merge($this->getAdminStats(), [
            'project_stats' => $this->repo->getProjectStats(),
            'vendor_performance' => $this->repo->getVendorPerformanceMetrics(),
        ]);
    }

    public function getProjectManagerStats(): array
    {
        return [
            'active_projects' => $this->repo->countActiveProjects(),
            'project_completion' => $this->repo->getProjectCompletionStats(),
            'pending_tasks' => $this->repo->countPendingTasks(),
            'team_utilization' => $this->repo->getTeamUtilization(),
        ];
    }

    public function getStaffStats(): array
    {
        return [
            'tenants' => $this->repo->countActiveTenants(),
            'new_tenants_this_month' => $this->repo->countNewTenantsThisMonth(),
            'open_maintenance' => $this->repo->countOpenMaintenanceRequests(),
            'high_priority_maintenance' => $this->repo->countMaintenanceByPriority('high'),
            'recent_logins' => $this->repo->getRecentLogins(5),
        ];
    }

    public function getChartData(string $viewMode): array
    {
        if ($viewMode === 'staff') {
            return [];
        }

        return [
            'revenue_trend' => $this->repo->getMonthlyRevenueTrend(12),
            'occupancy_trend' => $this->repo->getOccupancyTrend(12),
            'property_status' => [
                'occupied' => $this->repo->countOccupiedUnits(),
                'vacant' => $this->repo->countVacantUnits(),
                'maintenance' => $this->repo->countUnitsUnderMaintenance(),
            ],
            'rent_status' => $this->repo->getRentStatusDistribution(),
        ];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return [
            'tenants' => $this->repo->getRecentTenants($limit),
            'payments' => $this->repo->getRecentPayments($limit),
            'maintenance' => $this->repo->getRecentMaintenanceRequests($limit),
            'logins' => $this->repo->getRecentLogins($limit),
            'audit' => $this->auditRepo->getRecentActivity($limit),
        ];
    }

    public function getFinancialSummary(): array
    {
        return [
            'monthly_revenue' => $this->repo->getMonthlyRevenue(),
            'total_collected' => $this->repo->getTotalCollectedRent(),
            'outstanding_rent' => $this->repo->getOutstandingRent(),
            'collection_rate' => $this->repo->getCollectionRate(),
        ];
    }

    public function getAuditSummary(): array
    {
        return [
            'actions_24h' => $this->auditRepo->countActionsLast24h(),
            'failed_actions' => $this->auditRepo->countFailedActions(),
        ];
    }

    public function getTickerAlerts(int $limit = 10): array
    {
        $alerts = [];
        $rentDueAlerts = $this->repo->getRentDueAlerts($limit);

        foreach ($rentDueAlerts as $row) {
            $alerts[] = [
                'type' => 'Rent Due',
                'tenant' => (string) ($row['tenant'] ?? 'Unknown Tenant'),
                'property' => (string) ($row['property'] ?? 'Unknown Property'),
                'amount' => (float) ($row['amount'] ?? 0),
            ];
        }

        $debtorAlerts = $this->repo->getDebtorAlerts($limit);
        foreach ($debtorAlerts as $row) {
            $alerts[] = [
                'type' => 'Debtor',
                'tenant' => (string) ($row['tenant'] ?? 'Unknown Tenant'),
                'property' => 'Outstanding Balance',
                'amount' => (float) ($row['balance'] ?? 0),
            ];
        }

        return array_slice($alerts, 0, $limit);
    }
}
