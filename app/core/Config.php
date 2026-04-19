<?php
declare(strict_types=1);

final class Config
{
    public static function dbHost(): string
    {
        return $_ENV['DB_HOST'] ?? '127.0.0.1';
    }

    public static function dbUser(): string
    {
        return $_ENV['DB_USER'] ?? 'root';
    }

    public static function dbPass(): string
    {
        return $_ENV['DB_PASS'] ?? '';
    }

    public static function dbName(): string
    {
        return $_ENV['DB_NAME'] ?? 'mb_portal';
    }

    public static function dbPort(): int
    {
        return (int) ($_ENV['DB_PORT'] ?? 3306);
    }
}
