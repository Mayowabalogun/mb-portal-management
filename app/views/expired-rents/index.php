<?php require APP_ROOT . '/views/layouts/header.php'; ?>
<link href="<?= BASE_URL ?>/public/assets/css/expired-rents.css" rel="stylesheet">

<div class="container-fluid px-4 py-4 expired-rents-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">Expired Rents</h1>
            <p class="text-muted mb-0">Manage tenants whose rent period has ended and transition records to vacated.</p>
        </div>
        <span class="badge rounded-pill text-bg-danger fs-6"><?= number_format($expiredCount) ?> Expired</span>
    </div>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= htmlspecialchars((string) ($_SESSION['flash_type'] ?? 'info')) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars((string) $_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <form class="d-flex gap-2" method="get" action="">
                <input type="hidden" name="action" value="index">
                <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search tenant/property/unit">
                <button class="btn btn-sm btn-primary" type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($leaseStateSummary as $state => $count): ?>
            <div class="col-6 col-md-4 col-xl">
                <div class="card border-0 bg-white h-100 p-3 shadow-sm state-card">
                    <small class="text-muted d-block"><?= htmlspecialchars(str_replace('_', ' ', $state)) ?></small>
                    <strong><?= number_format((int) $count) ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($summary as $type => $data): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 bg-light h-100 p-3">
                    <h6 class="text-uppercase text-muted mb-2"><?= htmlspecialchars(ucfirst($type)) ?></h6>
                    <div class="fw-bold"><?= (int) ($data['expired_count'] ?? 0) ?> unit(s)</div>
                    <small class="text-muted">₦<?= number_format((float) ($data['total_outstanding'] ?? 0), 2) ?> outstanding</small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card shadow-sm data-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>SN</th>
                        <th>Tenant</th>
                        <th>Property / Unit</th>
                        <th>End Date</th>
                        <th>Days Expired</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $sn = ($page - 1) * $perPage + 1; ?>
                <?php foreach ($expiredRents as $row): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($row['tenant_name'] ?? 'Unknown')) ?></div>
                            <small class="text-muted"><?= htmlspecialchars((string) ($row['tenant_phone'] ?? '')) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($row['property_label'] ?? '')) ?>
                            <small class="text-muted d-block"><?= htmlspecialchars((string) ($row['unit_label'] ?? '')) ?></small>
                        </td>
                        <td><?= htmlspecialchars((string) ($row['end_date'] ?? '')) ?></td>
                        <td><span class="badge text-bg-warning"><?= (int) ($row['days_expired'] ?? 0) ?> days</span></td>
                        <td class="text-end">₦<?= number_format((float) ($row['balance_due'] ?? 0), 2) ?></td>
                        <td class="text-center">
                            <button
                                class="btn btn-sm btn-outline-danger js-open-vacate"
                                data-rent-id="<?= (int) ($row['rent_id'] ?? 0) ?>"
                                data-tenant-name="<?= htmlspecialchars((string) ($row['tenant_name'] ?? ''), ENT_QUOTES) ?>"
                                data-property="<?= htmlspecialchars((string) ($row['property_label'] ?? ''), ENT_QUOTES) ?>"
                                data-unit="<?= htmlspecialchars((string) ($row['unit_label'] ?? ''), ENT_QUOTES) ?>"
                                type="button"
                            >
                                Vacate
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($expiredRents)): ?>
                    <tr><td colspan="7" class="text-center py-4">No expired rents found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <ul class="pagination mb-0 justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?action=index&q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require APP_ROOT . '/views/expired-rents/_vacate-modal.php'; ?>
<script src="<?= BASE_URL ?>/public/assets/js/expired-rents.js"></script>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
