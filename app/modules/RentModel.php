<?php
declare(strict_types=1);

/**
 * Domain-only rent math model.
 *
 * IMPORTANT:
 * - No SQL
 * - No DB connection
 * - No orchestration
 *
 * Controllers/services/repositories should use this only for deterministic
 * money calculations.
 */
class RentModel
{
    public function calculateBalance(float $currentBalance, float $paymentAmount): float
    {
        return max(0.0, round($currentBalance - $paymentAmount, 2));
    }

    public function calculateTotalPaid(float $currentTotalPaid, float $paymentAmount): float
    {
        return round($currentTotalPaid + $paymentAmount, 2);
    }
}
