<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/AuthService.php';
require_once APP_ROOT . '/services/DashboardService.php';
require_once APP_ROOT . '/services/RentAlertService.php';
require_once APP_ROOT . '/Repository/SecurityRepository.php';
require_once APP_ROOT . '/utils/permissions.php';

class DashboardController
{
    private AuthService $authService;
    private DashboardService $dashboardService;
    private RentAlertService $rentAlertService;
    private SecurityRepository $securityRepo;

    private string $userLevel;
    private array $permissions;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->dashboardService = new DashboardService();
        $this->rentAlertService = new RentAlertService();
        $this->securityRepo = new SecurityRepository();
        $this->userLevel = $_SESSION['level'] ?? 'staff';
        $this->permissions = $_SESSION['permissions'] ?? [];
    }

    public function index(): void
    {
        if (!$this->authService->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/public/login.php');
            exit;
        }

        $this->renderDashboard();
    }

    private function renderDashboard(): void
    {
        $viewMode = $this->determineViewMode();
        $stats = $this->dashboardService->getDashboardStats($viewMode);
        $operationalAlerts = $this->buildOperationalAlerts($stats);
        $alerts = $this->dashboardService->getTickerAlerts(10);
        $alertCounts = ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];
        if ($this->rentAlertService->hasAlertStorage()) {
            $alerts = $this->rentAlertService->getTickerAlerts(10);
            $alertCounts = $this->rentAlertService->getAlertCounts();
        }
        $quickActions = $this->buildQuickActions();
        $menu = $this->buildCompleteMenu($stats);
        $links = $this->buildLinks();

        $lockedCount = $this->hasPermission('manage_security') && method_exists($this->securityRepo, 'getLockedAccounts')
            ? count($this->securityRepo->getLockedAccounts())
            : 0;

        $chartData = $this->dashboardService->getChartData($viewMode);
        $recentActivity = $this->dashboardService->getRecentActivity(10);
        $auditSummary = $this->dashboardService->getAuditSummary();
        $financialSummary = $this->hasPermission('view_financial_reports') ? $this->dashboardService->getFinancialSummary() : null;

        $page_title = $this->getPageTitle($viewMode);
        $user_level = $this->userLevel;
        $view_mode = $viewMode;
        $locked_count = $lockedCount;
        $operational_alerts = $operationalAlerts;
        $quick_actions = $quickActions;
        $rent_alerts = $alerts;
        $alert_counts = $alertCounts;

        require APP_ROOT . '/views/admin/dashboard_view.php';
    }

    private function buildOperationalAlerts(array $stats): array
    {
        $alerts = [];
        if (($stats['overdue_rent'] ?? 0) > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'fa-exclamation-circle', 'title' => 'Overdue Rent', 'message' => $stats['overdue_rent'] . ' tenant(s) with overdue payments', 'action_url' => 'rents/debt_list.php?filter=overdue', 'action_text' => 'View Debts'];
        }
        if (($stats['expiring_rent'] ?? 0) > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'fa-calendar-times', 'title' => 'Expiring Leases', 'message' => $stats['expiring_rent'] . ' lease(s) expiring soon', 'action_url' => 'rents/expired_list.php', 'action_text' => 'Review Leases'];
        }
        if (($stats['urgent_maintenance'] ?? 0) > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'fa-tools', 'title' => 'Urgent Maintenance', 'message' => $stats['urgent_maintenance'] . ' high-priority request(s)', 'action_url' => 'maintenance.php?priority=urgent', 'action_text' => 'View Requests'];
        }
        if (($stats['vacancy_rate'] ?? 0) > 15) {
            $alerts[] = ['type' => 'info', 'icon' => 'fa-door-open', 'title' => 'High Vacancy Rate', 'message' => number_format((float)$stats['vacancy_rate'], 1) . '% units vacant', 'action_url' => 'properties/list.php?status=vacant', 'action_text' => 'View Vacant Units'];
        }
        return $alerts;
    }

    private function buildQuickActions(): array
    {
        $actions = [];
        if ($this->hasPermission('manage_properties')) $actions[] = ['label' => 'Add Property', 'url' => 'properties/create.php', 'icon' => 'fa-plus-square', 'class' => 'btn-outline-primary'];
        if ($this->hasPermission('manage_tenants')) $actions[] = ['label' => 'Add Tenant', 'url' => 'tenants/create.php', 'icon' => 'fa-user-plus', 'class' => 'btn-outline-success'];
        if ($this->hasPermission('manage_landlords')) $actions[] = ['label' => 'Add Landlord', 'url' => 'landlords/create.php', 'icon' => 'fa-user-tie', 'class' => 'btn-outline-dark'];
        if ($this->hasPermission('manage_agreements')) $actions[] = ['label' => 'Create Rent', 'url' => 'rents/create.php', 'icon' => 'fa-file-signature', 'class' => 'btn-outline-secondary'];
        if ($this->hasPermission('process_payments')) $actions[] = ['label' => 'Record Payment', 'url' => 'payments.php?action=record', 'icon' => 'fa-money-bill-wave', 'class' => 'btn-outline-success'];
        return $actions;
    }

    private function buildCompleteMenu(array $stats): array
    {
        $lockedCount = $this->hasPermission('manage_security') && method_exists($this->securityRepo, 'getLockedAccounts') ? count($this->securityRepo->getLockedAccounts()) : 0;
        return [
            ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => $this->getDashboardUrl(), 'active' => true],
            ['icon' => 'fa-lock', 'label' => 'Unlock Accounts', 'url' => 'unlock-account.php', 'badge' => $lockedCount > 0 ? $lockedCount : null, 'highlight' => true],
            ['icon' => 'fa-users-cog', 'label' => 'Manage All Users', 'url' => 'users.php'],
        ];
    }

    private function buildLinks(): array
    {
        $base = BASE_URL . '/public';
        return [
            'dashboard' => $base . '/dashboard.php',
            'admin_dashboard' => $base . '/admin/admin-dashboard.php',
            'properties' => $base . '/properties/list.php',
            'tenants' => $base . '/tenants/list.php',
            'landlords' => $base . '/landlords/list.php',
            'rents' => $base . '/agreements.php',
            'maintenance' => $base . '/maintenance.php',
            'debt_management' => $base . '/rents/debt_list.php',
            'unlock_accounts' => $base . '/admin/unlock-account.php',
        ];
    }

    private function determineViewMode(): string
    {
        $normalizedLevel = strtolower($this->userLevel);
        if (in_array($normalizedLevel, ['super_admin (owner)', 'super_admin'], true)) {
            return 'super_admin';
        }
        if ($normalizedLevel === 'manager') {
            return 'manager';
        }
        if ($normalizedLevel === 'admin') {
            return 'admin';
        }

        if ($this->hasPermission('manage_system')) return 'super_admin';
        if ($this->hasPermission('view_admin_dashboard')) return 'admin';
        if ($this->hasPermission('manage_projects') && $this->hasPermission('view_financial_reports')) return 'manager';
        if ($this->hasPermission('manage_projects')) return 'project_manager';
        return 'staff';
    }

    private function hasPermission(string $permission): bool
    {
        return in_array('all', $this->permissions, true) || in_array($permission, $this->permissions, true);
    }

    private function getDashboardUrl(): string
    {
        return in_array($this->userLevel, ['Super_Admin (Owner)', 'Manager'], true)
            ? 'admin/admin-dashboard.php'
            : 'dashboard.php';
    }

    private function getPageTitle(string $viewMode): string
    {
        return match ($viewMode) {
            'super_admin' => 'Master Admin Dashboard | MB PropertyFinder',
            'admin' => 'Admin Dashboard | MB PropertyFinder',
            'manager' => 'Manager Dashboard | MB PropertyFinder',
            'project_manager' => 'Project Dashboard | MB PropertyFinder',
            default => 'Staff Dashboard | MB PropertyFinder',
        };
    }
}
