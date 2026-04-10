<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-lock text-danger me-2"></i>Unlock Accounts</h2>
        <div>Welcome, <strong><?= htmlspecialchars($current_user) ?></strong></div>
    </div>

    <?php if (!empty($_SESSION['flash']['message'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : htmlspecialchars((string)$f['type']) ?>"><?= htmlspecialchars((string)$f['message']) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5>Locked Accounts: <?= (int)$locked_count ?></h5>
        </div>
    </div>

    <?php if (empty($locked_accounts)): ?>
        <div class="alert alert-success">No accounts are currently locked.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($locked_accounts as $account): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-danger">
                        <div class="card-body">
                            <h5><?= htmlspecialchars((string)($account['username'] ?? 'Unknown')) ?></h5>
                            <p class="text-muted small">Failed attempts: <?= (int)($account['failed_attempts'] ?? 0) ?></p>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars((string)($account['username'] ?? '')) ?>">
                                <button type="submit" class="btn btn-warning w-100"><i class="fas fa-unlock me-2"></i>Unlock Account</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <h5>Manual Unlock</h5>
            <form method="POST" action="" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">
                <div class="col-md-8"><input type="text" class="form-control" name="username" placeholder="Enter username" required></div>
                <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Unlock Account</button></div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
