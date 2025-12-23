<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $st = $pdo->prepare('SELECT id, code, label_fr, label_en, is_active FROM occasions WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    $onlyActive = !isset($_GET['all']);
    if ($onlyActive) {
        $st = $pdo->query('SELECT id, code, label_fr, label_en, is_active FROM occasions WHERE is_active = TRUE ORDER BY label_fr');
    } else {
        $st = $pdo->query('SELECT id, code, label_fr, label_en, is_active FROM occasions ORDER BY label_fr');
    }

    api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $body = api_read_json_body();
    api_required($body, ['code', 'label_fr', 'label_en']);

    $isActive = array_key_exists('is_active', $body) ? (int)(bool)$body['is_active'] : 1;

    $st = $pdo->prepare('INSERT INTO occasions (code, label_fr, label_en, is_active) VALUES (?, ?, ?, ?)');
    $st->execute([
        (string)$body['code'],
        (string)$body['label_fr'],
        (string)$body['label_en'],
        $isActive,
    ]);

    api_send_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $body = api_read_json_body();
    $id = $id ?: (isset($body['id']) ? (int)$body['id'] : null);
    if (!$id) {
        api_fail('Missing id', 422);
    }

    $fields = [];
    $params = [];

    foreach (['code', 'label_fr', 'label_en'] as $k) {
        if (array_key_exists($k, $body)) {
            $fields[] = "$k = ?";
            $params[] = (string)$body[$k];
        }
    }

    if (array_key_exists('is_active', $body)) {
        $fields[] = 'is_active = ?';
        $params[] = (int)(bool)$body['is_active'];
    }

    if (!$fields) {
        api_fail('No fields to update', 422);
    }

    $params[] = $id;
    $sql = 'UPDATE occasions SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    api_send_json(['ok' => true]);
}

if ($method === 'DELETE') {
    $body = api_read_json_body();
    $id = $id ?: (isset($body['id']) ? (int)$body['id'] : null);
    if (!$id) {
        api_fail('Missing id', 422);
    }

    $st = $pdo->prepare('DELETE FROM occasions WHERE id = ?');
    $st->execute([$id]);

    api_send_json(['ok' => true]);
}

api_fail('Method not allowed', 405);
