<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $st = $pdo->prepare('SELECT id, owner_user_id, full_name, phone, gender, created_at FROM recipients WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    if (isset($_GET['owner_user_id'])) {
        $ownerUserId = (int)$_GET['owner_user_id'];
        $st = $pdo->prepare('SELECT id, owner_user_id, full_name, phone, gender, created_at FROM recipients WHERE owner_user_id = ? ORDER BY created_at DESC');
        $st->execute([$ownerUserId]);
        api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
    }

    $st = $pdo->query('SELECT id, owner_user_id, full_name, phone, gender, created_at FROM recipients ORDER BY created_at DESC');
    api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $body = api_read_json_body();
    api_required($body, ['owner_user_id', 'full_name']);

    $phone = array_key_exists('phone', $body) && $body['phone'] !== '' ? (string)$body['phone'] : null;
    $gender = array_key_exists('gender', $body) && $body['gender'] !== '' ? (string)$body['gender'] : null;

    try {
        $st = $pdo->prepare('INSERT INTO recipients (owner_user_id, full_name, phone, gender) VALUES (?, ?, ?, ?)');
        $st->execute([(int)$body['owner_user_id'], (string)$body['full_name'], $phone, $gender]);
        api_send_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
        if ($phone !== null && ($e->getCode() === '23000' || $e->getCode() === '1062')) {
            $st2 = $pdo->prepare('SELECT id FROM recipients WHERE phone = ?');
            $st2->execute([$phone]);
            $existing = $st2->fetch();
            if ($existing && isset($existing['id'])) {
                $st3 = $pdo->prepare('UPDATE recipients SET full_name = COALESCE(?, full_name), gender = COALESCE(?, gender), owner_user_id = COALESCE(?, owner_user_id) WHERE id = ?');
                $st3->execute([(string)$body['full_name'], $gender, (int)$body['owner_user_id'], (int)$existing['id']]);
                api_send_json(['ok' => true, 'id' => (int)$existing['id']], 200);
            }
        }

        api_fail('Failed to create recipient', 500);
    }
}

if ($method === 'PUT') {
    $body = api_read_json_body();
    $id = $id ?: (isset($body['id']) ? (int)$body['id'] : null);
    if (!$id) {
        api_fail('Missing id', 422);
    }

    $fields = [];
    $params = [];

    foreach (['owner_user_id', 'full_name', 'phone', 'gender'] as $k) {
        if (array_key_exists($k, $body)) {
            $fields[] = "$k = ?";
            if ($k === 'owner_user_id') {
                $params[] = (int)$body[$k];
            } else {
                $v = $body[$k];
                $params[] = ($v === '' ? null : $v);
            }
        }
    }

    if (!$fields) {
        api_fail('No fields to update', 422);
    }

    $params[] = $id;
    $sql = 'UPDATE recipients SET ' . implode(', ', $fields) . ' WHERE id = ?';
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

    $st = $pdo->prepare('DELETE FROM recipients WHERE id = ?');
    $st->execute([$id]);

    api_send_json(['ok' => true]);
}

api_fail('Method not allowed', 405);
