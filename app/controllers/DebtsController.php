<?php
declare(strict_types=1);

require_once APP_ROOT . '/modules/debts/DebtModule.php';

class DebtsController
{
    public function index(): void
    {
        $module = new DebtModule();
        $service = $module->service();

        $category = (string) ($_GET['category'] ?? 'all');
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $debts = $service->getDebtors($perPage, $offset, $category, $search);
        $total = $service->countDebtors($category, $search);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $summary = $service->getSummary();

        $alertData = $service->getHeaderAlerts();
        $rent_alerts = $alertData['alerts'] ?? [];
        $alert_counts = $alertData['counts'] ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];

        $page_title = 'Rent Debt Management | MB PropertyFinder';
        $active_page = 'rents';

        require APP_ROOT . '/views/debts/index.php';
    }

    public function history(): void
    {
        $module = new DebtModule();
        $service = $module->service();

        $rentId = (int) ($_GET['rent_id'] ?? 0);
        $debt = $service->getDebtByRentId($rentId);
        if (!$debt) {
            http_response_code(404);
            exit('Debt record not found.');
        }

        $history = $service->getPaymentHistory($rentId);
        $alertData = $service->getHeaderAlerts();
        $rent_alerts = $alertData['alerts'] ?? [];
        $alert_counts = $alertData['counts'] ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];
        $page_title = 'Payment History | MB PropertyFinder';
        $active_page = 'rents';
        require APP_ROOT . '/views/debts/payment_history.php';
    }

    public function makePayment(): void
    {
        $module = new DebtModule();
        $service = $module->service();
        $rentId = (int) ($_GET['rent_id'] ?? $_POST['rent_id'] ?? 0);
        $debt = $service->getDebtByRentId($rentId);
        if (!$debt) {
            http_response_code(404);
            exit('Debt record not found.');
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $amount = (float) ($_POST['amount'] ?? 0);
            $method = (string) ($_POST['method'] ?? 'Cash');
            if ($amount <= 0) {
                $error = 'Payment amount must be greater than zero.';
            } elseif (!$service->recordPayment((int) $debt['rent_id'], (int) $debt['tenant_id'], $amount, $method)) {
                $error = 'Payment could not be saved.';
            } else {
                header('Location: ' . BASE_URL . '/public/debts/index.php');
                exit;
            }
        }

        $alertData = $service->getHeaderAlerts();
        $rent_alerts = $alertData['alerts'] ?? [];
        $alert_counts = $alertData['counts'] ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];
        $page_title = 'Make Debt Payment | MB PropertyFinder';
        $active_page = 'rents';
        require APP_ROOT . '/views/debts/make_payment.php';
    }
}
