<?php require APP_ROOT . '/views/layouts/header.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Make Debt Payment</h1>
        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/public/debts/index.php">Back</a>
    </div>
    <div class="alert alert-warning">
        You are about to make payment for <strong><?= htmlspecialchars((string)$debt['tenant_name']) ?></strong>.
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div><?php endif; ?>
    <form method="post" class="card card-body">
        <input type="hidden" name="rent_id" value="<?= (int)$debt['rent_id'] ?>">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Total Rent</label><input class="form-control" value="<?= number_format((float)$debt['t_rent'],2) ?>" readonly></div>
            <div class="col-md-4"><label class="form-label">Total Paid</label><input class="form-control" value="<?= number_format((float)$debt['total_pay'],2) ?>" readonly></div>
            <div class="col-md-4"><label class="form-label">Balance Due</label><input class="form-control" value="<?= number_format((float)$debt['balance_due'],2) ?>" readonly></div>
            <div class="col-md-6"><label class="form-label">Amount to Pay</label><input type="number" step="0.01" min="0.01" max="<?= htmlspecialchars((string)$debt['balance_due']) ?>" class="form-control" name="amount" required></div>
            <div class="col-md-6"><label class="form-label">Method</label><select class="form-select" name="method"><option>Cash</option><option>Transfer</option><option>POS</option><option>Bank</option></select></div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-success">Make Payment</button>
        </div>
    </form>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
