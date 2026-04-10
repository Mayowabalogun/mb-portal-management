<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title ?? 'MB PropertyFinder') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/dashboard.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/ticker.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/dashboard-alerts.css" rel="stylesheet">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body class="bg-light">

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alertCounts = $alert_counts ?? ['critical' => 0, 'warning' => 0, 'info' => 0, 'total_unresolved' => 0];
$rentAlerts = $rent_alerts ?? $alerts ?? [];

$sessionRole = $_SESSION['role'] ?? $_SESSION['level'] ?? 'staff';
$currentRole = strtolower((string) $sessionRole);
$currentUsername = $_SESSION['username'] ?? 'User';

$badgeClass = 'bg-secondary';
if (in_array($currentRole, ['super_admin', 'super_admin (owner)'], true)) {
    $badgeClass = 'bg-danger';
} elseif (in_array($currentRole, ['admin', 'manager'], true)) {
    $badgeClass = 'bg-warning text-dark';
}
?>

<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-primary app-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/public/admin/admin-dashboard.php">
            <i class="bi bi-building me-2"></i>MB REAL ESTATE AGENCY
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative text-white" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if (($alertCounts['total_unresolved'] ?? 0) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= (int) $alertCounts['total_unresolved'] ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow alert-dropdown-menu">
                        <li class="dropdown-header fw-bold">Rent Alerts</li>
                        <?php if (empty($rentAlerts)): ?>
                            <li class="px-3 py-2 text-muted small">No active alerts</li>
                        <?php else: ?>
                            <?php foreach ($rentAlerts as $a): ?>
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-semibold small"><?= htmlspecialchars((string) ($a['tenant_name'] ?? 'Unknown Tenant')) ?></div>
                                    <div class="small text-muted">
                                        ₦<?= number_format((float) ($a['rent_amount'] ?? 0), 2) ?>
                                        • <?= htmlspecialchars((string) ($a['alert_type'] ?? 'alert')) ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-center" href="<?= BASE_URL ?>/public/rent-debts.php">View All Debts</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <span class="badge <?= $badgeClass ?> text-uppercase" style="font-size:0.7rem;">
                        <?= htmlspecialchars(str_replace('_', ' ', (string) $sessionRole)) ?>
                    </span>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars((string) $currentUsername) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/admin/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <?php if (in_array($currentRole, ['admin', 'manager', 'super_admin', 'super_admin (owner)'], true)): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/public/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="sidebar bg-white border-end shadow-sm app-sidebar">
    <?php include APP_ROOT . '/views/layouts/sidebar.php'; ?>
</div>

<main class="main-content app-main-content">
    <div class="alert-indicators d-flex flex-wrap gap-2 mb-3">
        <?php if (($alertCounts['critical'] ?? 0) > 0): ?>
            <span class="badge bg-danger"><i class="bi bi-exclamation-octagon-fill"></i> <?= (int) $alertCounts['critical'] ?> Critical</span>
        <?php endif; ?>
        <?php if (($alertCounts['warning'] ?? 0) > 0): ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> <?= (int) $alertCounts['warning'] ?> Warning</span>
        <?php endif; ?>
        <?php if (($alertCounts['info'] ?? 0) > 0): ?>
            <span class="badge bg-info text-dark"><i class="bi bi-info-circle-fill"></i> <?= (int) $alertCounts['info'] ?> Upcoming</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($rentAlerts) && is_array($rentAlerts)): ?>
        <?php $rent_alerts = $rentAlerts; include APP_ROOT . '/views/components/alert-ticker.php'; ?>
    <?php endif; ?>
