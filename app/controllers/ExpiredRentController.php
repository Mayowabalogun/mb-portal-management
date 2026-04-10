<?php
declare(strict_types=1);

require_once APP_ROOT . '/modules/ExpiredRentModules.php';

class ExpiredRentController
{
    public function index(): void
    {
        $module = new ExpiredRentModules();
        $service = $module->service();

        $role = (string) ($_SESSION['level'] ?? '');
        $entityId = isset($_SESSION['entity_id']) ? (int) $_SESSION['entity_id'] : null;

        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        $rows = $service->getExpiredRents($role, $entityId);
        if ($search !== '') {
            $query = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($query): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($row['tenant_name'] ?? ''),
                    (string) ($row['tenant_phone'] ?? ''),
                    (string) ($row['property_label'] ?? ''),
                    (string) ($row['unit_label'] ?? ''),
                ]));
                return str_contains($haystack, $query);
            }));
        }

        $total = count($rows);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $expiredRents = array_slice($rows, $offset, $perPage);

        $summary = $service->getExpirySummaryByType();
        $leaseStateSummary = $service->getLeaseStateSummary($role, $entityId);
        $expiredCount = $service->countExpiredRents($role, $entityId);

        $alertData = $service->getHeaderAlerts();
        $rent_alerts = $alertData['alerts'] ?? [];
        $alert_counts = $alertData['counts'] ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];

        $page_title = 'Expired Rents | MB PropertyFinder';
        $active_page = 'expired-rents';
        require APP_ROOT . '/views/expired-rents/index.php';
    }

    public function vacate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        $module = new ExpiredRentModules();
        $service = $module->service();

        $rentId = (int) ($_POST['rent_id'] ?? 0);
        $role = (string) ($_SESSION['level'] ?? '');
        $entityId = isset($_SESSION['entity_id']) ? (int) $_SESSION['entity_id'] : null;

        $ok = $service->vacateByExpiry($rentId, $role, $entityId);
        $_SESSION['flash_message'] = $ok
            ? 'Rent marked as vacated successfully.'
            : 'Unable to vacate rent. Verify ownership and status.';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';

        header('Location: ' . BASE_URL . '/public/expired-rents.php');
        exit;
    }
}
