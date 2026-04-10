<?php
declare(strict_types=1);

if (!function_exists('send_documents_to_parties')) {
    function send_documents_to_parties(array $payload): array
    {
        // Stub adapter for local environments.
        return ['status' => 'queued', 'id' => null, 'payload' => $payload];
    }
}
