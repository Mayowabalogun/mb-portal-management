<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/session.php';
require_once APP_ROOT . '/views/layouts/header.php';
?>
<div class="container-fluid py-4 mt-5">
    <h1 class="h2 mb-3"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
    <p class="text-muted"><?= htmlspecialchars((string)($stats['user_greeting'] ?? 'Welcome')) ?> | <?= htmlspecialchars((string)($stats['current_date'] ?? date('Y-m-d'))) ?></p>

    <div class="row g-3">
        <div class="col-md-3"><div class="card p-3 bg-info text-white"><h4><?= format_number((int)($stats['properties'] ?? 0)) ?></h4><small>Properties</small></div></div>
        <div class="col-md-3"><div class="card p-3 bg-success text-white"><h4><?= format_number((int)($stats['tenants'] ?? 0)) ?></h4><small>Tenants</small></div></div>
        <div class="col-md-3"><div class="card p-3 bg-primary text-white"><h4><?= format_number((int)($stats['landlords'] ?? 0)) ?></h4><small>Landlords</small></div></div>
        <div class="col-md-3"><div class="card p-3 bg-secondary text-white"><h4><?= format_number((int)($stats['active_rents'] ?? 0)) ?></h4><small>Active Rents</small></div></div>
    </div>
</div>
<?php require_once APP_ROOT . '/views/layouts/footer.php'; ?>
