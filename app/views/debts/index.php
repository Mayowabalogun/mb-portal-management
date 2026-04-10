<?php require APP_ROOT . '/views/layouts/header.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold">Rent Debt Management</h1>
        <form class="d-flex gap-2" method="get" action="">
            <input type="hidden" name="action" value="index">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search tenant/property/unit">
            <button class="btn btn-sm btn-primary" type="submit">Search</button>
        </form>
    </div>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="?action=index&category=all" class="btn btn-sm btn-outline-secondary">All</a>
        <a href="?action=index&category=soft" class="btn btn-sm btn-outline-warning">Soft</a>
        <a href="?action=index&category=hard" class="btn btn-sm btn-outline-danger">Hard</a>
        <a href="?action=index&category=critical" class="btn btn-sm btn-outline-dark">Critical</a>
        <a href="?action=index&category=legal" class="btn btn-sm btn-outline-danger">Legal</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card bg-warning-subtle border-0 p-3"><h5><?= number_format((int)($summary['soft_count'] ?? 0)) ?></h5><small>Soft</small></div></div>
        <div class="col-md-2"><div class="card bg-danger-subtle border-0 p-3"><h5><?= number_format((int)($summary['hard_count'] ?? 0)) ?></h5><small>Hard</small></div></div>
        <div class="col-md-2"><div class="card bg-dark text-white border-0 p-3"><h5><?= number_format((int)($summary['critical_count'] ?? 0)) ?></h5><small>Critical</small></div></div>
        <div class="col-md-2"><div class="card bg-danger text-white border-0 p-3"><h5><?= number_format((int)($summary['legal_count'] ?? 0)) ?></h5><small>Legal</small></div></div>
        <div class="col-md-4"><div class="card bg-primary text-white border-0 p-3"><h5>₦<?= number_format((float)($summary['total_outstanding'] ?? 0), 2) ?></h5><small>Total Outstanding</small></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light"><tr><th>SN</th><th>Category</th><th>Tenant</th><th>Property/Unit</th><th>End Date</th><th>Days</th><th class="text-end">Balance</th><th><i class="fa fa-edit fa-fw"></i></th><th><i class="fa fa-edit fa-fw"></i></th></tr></thead>
                <tbody>
                <?php $sn = ($page - 1) * $perPage + 1; ?>
                <?php foreach ($debts as $debt): ?>
                    <tr>
                        <td><?= $sn++ ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string)$debt['category']) ?></span></td>
                        <td><?= htmlspecialchars((string)($debt['full_name'] ?? 'Unknown')) ?><br><small class="text-muted"><?= htmlspecialchars((string)($debt['phone'] ?? '')) ?></small></td>
                        <td><?= htmlspecialchars((string)($debt['property_label'] ?? '')) ?> <small class="text-muted"><?= htmlspecialchars((string)($debt['unit_label'] ?? '')) ?></small></td>
                        <td><?= htmlspecialchars((string)($debt['end_date'] ?? '')) ?></td>
                        <td><?= (int)($debt['days_overdue'] ?? 0) ?></td>
                        <td class="text-end">₦<?= number_format((float)($debt['balance_due'] ?? 0), 2) ?></td>
                        <td title="Payment Details"><a href="?action=history&rent_id=<?= (int)$debt['rent_id'] ?>"><i class="fa fa-search"></i></a></td>
                        <td title="Make Payment">
                            <?php if (($debt['status'] ?? '') === 'Rent Cancel'): ?>
                                <i class="fa fa-search text-muted"></i>
                            <?php else: ?>
                                <a href="?action=make-payment&rent_id=<?= (int)$debt['rent_id'] ?>"><i class="fa fa-money"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($debts)): ?><tr><td colspan="9" class="text-center py-4">No debtors found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?action=index&category=<?= urlencode($category) ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
