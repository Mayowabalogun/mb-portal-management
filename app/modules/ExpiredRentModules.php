<?php
declare(strict_types=1);

require_once APP_ROOT . '/services/RentExpiryService.php';

class ExpiredRentModules
{
    public function service(): RentExpiryService
    {
        return new RentExpiryService();
    }
}
