<div class="ticker-wrapper mb-3" data-alert-ticker>
    <div class="ticker-heading">
        <i class="bi bi-megaphone-fill me-1"></i>
        Rent Alerts
    </div>

    <ul class="ticker ticker-inner mb-0">
        <?php foreach (($rent_alerts ?? $alerts ?? []) as $alert): ?>
            <?php
            $priority = (string) ($alert['priority'] ?? 'info');
            $icon = match ($priority) {
                'critical' => 'bi-exclamation-octagon-fill text-danger',
                'warning' => 'bi-exclamation-triangle-fill text-warning',
                default => 'bi-info-circle-fill text-info',
            };

            $alertType = (string) ($alert['alert_type'] ?? $alert['type'] ?? 'due_soon');
            $badge = match ($alertType) {
                'due_soon' => '5 Days',
                'due_today' => 'TODAY',
                'grace_period' => 'Grace',
                'overdue' => ((int) ($alert['days_overdue'] ?? 0)) . ' Days Overdue',
                'default' => 'DEFAULT RISK',
                default => strtoupper(str_replace('_', ' ', $alertType)),
            };

            $tenantName = (string) ($alert['tenant_name'] ?? $alert['tenant'] ?? 'Unknown Tenant');
            $propertyAddress = (string) ($alert['property_address'] ?? $alert['property'] ?? 'Unknown Property');
            $amount = (float) ($alert['rent_amount'] ?? $alert['amount'] ?? 0);
            ?>
            <li>
                <i class="bi <?= $icon ?> me-2"></i>
                <strong><?= htmlspecialchars($tenantName) ?></strong>
                at <?= htmlspecialchars($propertyAddress) ?>
                <span class="badge bg-dark ms-2"><?= htmlspecialchars($badge) ?></span>
                <strong class="ms-2">₦<?= number_format($amount, 2) ?></strong>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
