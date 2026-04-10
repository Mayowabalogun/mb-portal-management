<?php
declare(strict_types=1);

/**
 * Database Connection File
 * Location: app/connections/db.php
 */

require_once __DIR__ . '/../config/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * BUSINESS DATABASE CONNECTION
 */
$businessDb = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$businessDb->set_charset('utf8mb4');

/**
 * AUTH DATABASE CONNECTION (optional)
 */
$authDb = null;

if (defined('AUTH_DB_NAME') && AUTH_DB_NAME !== '') {
    $authDb = new mysqli(
        AUTH_DB_HOST,
        AUTH_DB_USER,
        AUTH_DB_PASS,
        AUTH_DB_NAME,
        AUTH_DB_PORT
    );
    $authDb->set_charset('utf8mb4');
}

/**
 * CONNECTION ACCESSORS
 */
function getBusinessConnection(): mysqli
{
    global $businessDb;

    return $businessDb;
}

function getAuthConnection(): ?mysqli
{
    global $authDb;

    return $authDb;
}

/**
 * Backward compatibility (legacy)
 * DO NOT use in new code.
 */
function getConnection(): mysqli
{
    return getBusinessConnection();
}
