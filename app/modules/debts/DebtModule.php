<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/DebtService.php';

class DebtModule
{
    public function service(): DebtService
    {
        return new DebtService();
    }
}
