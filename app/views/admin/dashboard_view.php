<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/session.php';
$active_page = 'dashboard';
$page_scripts = [BASE_URL . '/public/assets/js/dashboard-alerts.js'];
require APP_ROOT . '/views/layouts/header.php';

$normalizedLevel = strtolower((string) ($user_level ?? $_SESSION['level'] ?? 'staff'));
$roleLabel = match ($normalizedLevel) {
    'super_admin (owner)', 'super_admin' => 'Super Admin',
    'manager' => 'Manager',
    'admin' => 'Admin',
    default => 'Staff',
};
$badgeClass = match ($normalizedLevel) {
    'super_admin (owner)', 'super_admin' => 'bg-danger',
    'manager' => 'bg-warning text-dark',
    'admin' => 'bg-primary',
    default => 'bg-secondary',
};
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between flex-wrap align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h1 class="h3 fw-bold text-dark"><?= htmlspecialchars((string) $page_title) ?></h1>
            <p class="text-muted mb-0">
                <i class="fas fa-user-shield me-1"></i>
                Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong> |
                <span class="text-primary"><?= date('l, F j, Y') ?></span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?= $badgeClass ?> p-2 px-3 rounded-pill shadow-sm">
                <i class="fas fa-id-badge me-1"></i> <?= htmlspecialchars($roleLabel) ?>
            </span>
            <?php if (!empty($locked_count)): ?>
                <a href="<?= BASE_URL ?>/public/admin/unlock-account.php" class="btn btn-sm btn-warning shadow-sm fw-bold" id="lockedAlertBtn">
                    <i class="fas fa-lock me-1"></i> <?= (int) $locked_count ?> Locked
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($operational_alerts)): ?>
        <?php foreach ($operational_alerts as $alert): ?>
            <div class="alert alert-<?= htmlspecialchars((string) $alert['type']) ?> d-flex justify-content-between align-items-center shadow-sm mb-3">
                <span>
                    <i class="fas <?= htmlspecialchars((string) ($alert['icon'] ?? 'fa-info-circle')) ?> me-2"></i>
                    <strong><?= htmlspecialchars((string) $alert['title']) ?>:</strong>
                    <?= htmlspecialchars((string) $alert['message']) ?>
                </span>
                <a href="<?= BASE_URL ?>/public/<?= htmlspecialchars((string) $alert['action_url']) ?>" class="btn btn-sm btn-outline-dark px-3">
                    <?= htmlspecialchars((string) $alert['action_text']) ?>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="d-flex align-items-center mb-2">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-shield-halved me-2 text-secondary"></i>Audit Summary</h6>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 class="fw-bold mb-0"><?= number_format((int)($stats['properties'] ?? 0)) ?></h3><small class="opacity-75">Properties</small></div>
                    <i class="fas fa-building fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 class="fw-bold mb-0"><?= number_format((int)($stats['tenants'] ?? 0)) ?></h3><small class="opacity-75">Tenants</small></div>
                    <i class="fas fa-users fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 class="fw-bold mb-0"><?= number_format((int)($stats['total_units'] ?? 0)) ?></h3><small class="opacity-75">Total Units</small></div>
                    <i class="fas fa-door-open fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div><h3 class="fw-bold mb-0"><?= number_format((int)($stats['active_rents'] ?? 0)) ?></h3><small class="opacity-75">Active Agreements</small></div>
                    <i class="fas fa-file-contract fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-light p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Actions (24h)</h6>
                        <h3 class="fw-bold mb-0"><?= number_format((int) ($auditSummary['actions_24h'] ?? 0)) ?></h3>
                    </div>
                    <i class="fas fa-clipboard-list fa-2x text-primary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-light p-3 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Failed Actions</h6>
                        <h3 class="fw-bold mb-0 text-danger"><?= number_format((int) ($auditSummary['failed_actions'] ?? 0)) ?></h3>
                    </div>
                    <i class="fas fa-triangle-exclamation fa-2x text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line me-2 text-primary"></i>Revenue Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="height: 300px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-pie me-2 text-success"></i>Occupancy Status</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div style="height: 220px;"><canvas id="propertyStatusChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-info"></i>System Activity Log</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 ps-4">Timestamp</th>
                                    <th class="border-0">Event</th>
                                    <th class="border-0">Entity</th>
                                    <th class="border-0">Actor</th>
                                    <th class="border-0 pe-4">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentActivity['audit'])): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No audit activity detected.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($recentActivity['audit'], 0, 8) as $audit): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= date('M d, Y | H:i', strtotime((string) ($audit['created_at'] ?? 'now'))) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars((string) ($audit['event_key'] ?? 'unknown.event')) ?>
                                            </span>
                                            <small class="text-muted ms-1"><?= htmlspecialchars((string) ($audit['source'] ?? 'audit')) ?></small>
                                        </td>
                                        <td class="small">
                                            <?= htmlspecialchars((string) ($audit['entity_type'] ?? 'system')) ?>
                                            <?php if (!empty($audit['entity_id'])): ?>
                                                #<?= (int) $audit['entity_id'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars((string) ($audit['actor_name'] ?? 'system')) ?></td>
                                        <td class="text-muted pe-4 small"><?= htmlspecialchars((string) ($audit['ip_address'] ?? '-')) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-area me-2 text-primary"></i>Activity Trends (7 Days)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($auditSummary['activity_trends'])): ?>
                        <p class="text-muted mb-0">No activity trend data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Events</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditSummary['activity_trends'] as $trend): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($trend['day_key'] ?? '')) ?></td>
                                            <td class="text-end fw-semibold"><?= number_format((int) ($trend['total_events'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-triangle-exclamation me-2 text-danger"></i>Critical Alerts</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($auditSummary['critical_alerts'])): ?>
                        <p class="text-muted mb-0">No critical alerts.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($auditSummary['critical_alerts'], 0, 6) as $critical): ?>
                                <li class="list-group-item px-0">
                                    <div class="fw-semibold text-danger"><?= htmlspecialchars((string) ($critical['event_key'] ?? 'unknown')) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars((string) ($critical['source'] ?? 'system')) ?>
                                        • <?= htmlspecialchars((string) ($critical['created_at'] ?? '')) ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        new Chart(revCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chartData['revenue_trend'] ?? [], 'month')) ?>,
                datasets: [{
                    label: 'Revenue (₦)',
                    data: <?= json_encode(array_column($chartData['revenue_trend'] ?? [], 'amount')) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.05)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }

    const statusCtx = document.getElementById('propertyStatusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Vacant', 'Maintenance'],
                datasets: [{
                    data: [
                        <?= (int)($chartData['property_status']['occupied'] ?? 0) ?>,
                        <?= (int)($chartData['property_status']['vacant'] ?? 0) ?>,
                        <?= (int)($chartData['property_status']['maintenance'] ?? 0) ?>
                    ],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderWidth: 0,
                    cutout: '75%'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }
});
</script>

<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>
