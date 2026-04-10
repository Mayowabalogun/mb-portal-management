<?php
$role = strtolower((string) ($_SESSION['role'] ?? $_SESSION['level'] ?? 'staff'));
$activePage = $active_page ?? '';
?>
<nav class="flex-column p-3">
    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-1 text-muted text-uppercase small fw-bold">
        <span>Navigation</span>
    </h6>

    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active shadow-sm' : 'text-dark' ?>"
               href="<?= BASE_URL ?>/public/admin/admin-dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        </li>
    </ul>

    <?php if (in_array($role, ['admin', 'manager', 'super_admin', 'super_admin (owner)'], true)): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase small fw-bold">
            <span>Rent &amp; Payments</span>
        </h6>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'rents' ? 'active shadow-sm' : 'text-dark' ?>" href="<?= BASE_URL ?>/public/debts/index.php">
                    <i class="bi bi-currency-exchange me-2"></i>Rent Debts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'expired-rents' ? 'active shadow-sm' : 'text-dark' ?>" href="<?= BASE_URL ?>/public/expired-rents.php">
                    <i class="bi bi-clock me-2"></i>Expiring Rents
                </a>
            </li>
            <?php if (in_array($role, ['super_admin', 'super_admin (owner)'], true)): ?>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?= BASE_URL ?>/public/admin/rollback-rents.php">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Rollback Rents
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase small fw-bold">
        <span>Properties &amp; People</span>
    </h6>
    <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link text-dark" href="<?= BASE_URL ?>/public/admin/properties.php">
                <i class="bi bi-house-door me-2"></i>Properties
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-dark" href="<?= BASE_URL ?>/public/admin/tenants.php">
                <i class="bi bi-people me-2"></i>Tenants
            </a>
        </li>
        <?php if (in_array($role, ['admin', 'manager', 'super_admin', 'super_admin (owner)'], true)): ?>
            <li class="nav-item">
                <a class="nav-link text-dark" href="<?= BASE_URL ?>/public/admin/landlords.php">
                    <i class="bi bi-person-badge me-2"></i>Landlords
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if (in_array($role, ['super_admin', 'super_admin (owner)'], true)): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase small fw-bold">
            <span>Administration</span>
        </h6>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link text-dark" href="<?= BASE_URL ?>/public/admin/users.php">
                    <i class="bi bi-people-fill me-2"></i>User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-warning" href="<?= BASE_URL ?>/public/admin/unlock-account.php">
                    <i class="bi bi-unlock me-2"></i>Unlock Accounts
                </a>
            </li>
        </ul>
    <?php endif; ?>
</nav>
