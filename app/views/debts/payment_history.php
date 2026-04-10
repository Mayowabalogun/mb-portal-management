<?php require APP_ROOT . '/views/layouts/header.php'; ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Payment History</h1>
        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/public/debts/index.php">Back</a>
    </div>
    <div class="alert alert-info">
        <strong><?= htmlspecialchars((string)$debt['tenant_name']) ?></strong> — <?= htmlspecialchars((string)$debt['property_label']) ?> (<?= htmlspecialchars((string)$debt['unit_label']) ?>)
        <br>Total Rent: ₦<?= number_format((float)$debt['t_rent'],2) ?> | Paid: ₦<?= number_format((float)$debt['total_pay'],2) ?> | Balance: ₦<?= number_format((float)$debt['balance_due'],2) ?>
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>SN</th><th>Amount Paid</th><th>Balance After</th><th>Payment Date</th><th>Method</th><th>Receipt</th></tr></thead><tbody>
        <?php $sn = 1; $running = (float)$debt['balance_due']; ?>
        <?php foreach ($history as $row): $running += (float)$row['amount']; ?>
            <tr><td><?= $sn++ ?></td><td>₦<?= number_format((float)$row['amount'],2) ?></td><td>~</td><td><?= htmlspecialchars((string)$row['payment_date']) ?></td><td><?= htmlspecialchars((string)$row['payment_method']) ?></td><td><?= htmlspecialchars((string)$row['receipt_no']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($history)): ?><tr><td colspan="6" class="text-center">No payments recorded.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</div>
<?php require APP_ROOT . '/views/layouts/footer.php'; ?>
