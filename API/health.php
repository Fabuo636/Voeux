<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

try {
    $pdo = api_pdo();
    $pdo->query('SELECT 1');
    api_send_json(['ok' => true, 'status' => 'up']);
} catch (Throwable $e) {
    api_send_json(['ok' => false, 'status' => 'down'], 500);
}
