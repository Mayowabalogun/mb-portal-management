<?php
declare(strict_types=1);

if (!function_exists('has_permission')) {
    function has_permission(string $permission): bool
    {
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array('all', $permissions, true) || in_array($permission, $permissions, true);
    }
}

if (!function_exists('format_number')) {
    function format_number(int|float $value): string
    {
        return number_format($value);
    }
}
