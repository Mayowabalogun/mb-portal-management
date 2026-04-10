<?php
declare(strict_types=1);

final class HomeModule
{
    public static function viewPath(): string
    {
        return APP_ROOT . '/views/home/index.php';
    }
}
