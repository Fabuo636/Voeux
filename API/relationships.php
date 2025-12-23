<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    api_fail('Method not allowed', 405);
}

$onlyActive = true;
if (isset($_GET['active'])) {
    $onlyActive = ((string)$_GET['active'] !== '0');
}

$where = $onlyActive ? 'WHERE is_active = 1' : '';

$st = $pdo->query('SELECT id, code, label_fr, label_en, is_active FROM relationships ' . $where . ' ORDER BY id ASC');
$rows = $st->fetchAll();

api_send_json([
    'ok' => true,
    'data' => array_map(static function ($r) {
        return [
            'id' => (int)$r['id'],
            'code' => (string)$r['code'],
            'label_fr' => (string)$r['label_fr'],
            'label_en' => (string)$r['label_en'],
            'is_active' => (int)$r['is_active'] === 1,
        ];
    }, $rows),
]);
