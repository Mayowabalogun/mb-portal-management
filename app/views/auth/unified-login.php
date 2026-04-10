<?php
declare(strict_types=1);

$config = $config ?? ['title' => 'Sign In', 'subtitle' => 'Access your account', 'theme' => 'default'];
$portal = $portal ?? 'main';
$csrfToken = $csrfToken ?? '';
$error = $error ?? '';
$redirect = $redirect ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title']) ?> - MB Real Estate Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body{font-family:'Open Sans',sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .login-container{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:460px;margin:20px;overflow:hidden}
        .login-header{background:#2563eb;color:#fff;padding:2rem;text-align:center}
        .login-header.tenant{background:#059669}.login-header.landlord{background:#7c3aed}.login-header.vendor{background:#ea580c}.login-header.partner{background:#0891b2}.login-header.admin{background:#dc2626}
        .login-body{padding:2rem}.portal-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem}
        .portal-link{display:flex;align-items:center;padding:.5rem;border-radius:8px;text-decoration:none;color:#374151}.portal-link:hover{background:#f3f4f6}
    </style>
</head>
<body>
<div class="login-container">
    <!-- Header switches color/title by selected portal while keeping one login endpoint -->
    <div class="login-header <?= htmlspecialchars((string) $config['theme']) ?>">
        <h3 class="mb-1"><?= htmlspecialchars((string) $config['title']) ?></h3>
        <p class="mb-0 opacity-75"><?= htmlspecialchars((string) $config['subtitle']) ?></p>
    </div>
    <div class="login-body">
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if (!empty($_SESSION['flash']['message'])): ?><div class="alert alert-<?= htmlspecialchars((string) ($_SESSION['flash']['type'] ?? 'info')) ?>"><?= htmlspecialchars((string) $_SESSION['flash']['message']) ?></div><?php unset($_SESSION['flash']); endif; ?>

        <form method="POST" action="">
            <!-- Security + routing context -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="portal" value="<?= htmlspecialchars($portal) ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required autofocus>
                <label for="username">Username or Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Remember me</label></div>
                <a href="forgot-password.php">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>

        <?php if ($portal === 'main'): ?>
            <!-- Portal shortcuts still submit to the same unified login page -->
            <div class="mt-4 pt-3 border-top">
                <h6 class="text-muted text-uppercase small mb-3">Select Portal</h6>
                <div class="portal-grid">
                    <a href="?portal=tenant" class="portal-link"><i class="fas fa-user me-2"></i>Tenant</a>
                    <a href="?portal=landlord" class="portal-link"><i class="fas fa-building me-2"></i>Landlord</a>
                    <a href="?portal=vendor" class="portal-link"><i class="fas fa-tools me-2"></i>Vendor</a>
                    <a href="?portal=partner" class="portal-link"><i class="fas fa-handshake me-2"></i>Partner</a>
                    <a href="?portal=staff" class="portal-link"><i class="fas fa-user-tie me-2"></i>Staff</a>
                    <a href="?portal=admin" class="portal-link"><i class="fas fa-shield-alt me-2"></i>Admin</a>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-3 text-center"><a href="login.php">Back to main login</a></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
