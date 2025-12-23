<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $st = $pdo->prepare('SELECT id, full_name, email, Tel, created_at FROM users WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    $st = $pdo->query('SELECT id, full_name, email, Tel, created_at FROM users ORDER BY created_at DESC');
    api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $body = api_read_json_body();
    api_required($body, ['full_name']);

    $email = array_key_exists('email', $body) && $body['email'] !== '' ? (string)$body['email'] : null;

    $tel = null;
    if (array_key_exists('Tel', $body) && $body['Tel'] !== '' && $body['Tel'] !== null) {
        $tel = (string)$body['Tel'];
    } elseif (array_key_exists('tel', $body) && $body['tel'] !== '' && $body['tel'] !== null) {
        $tel = (string)$body['tel'];
    } elseif (array_key_exists('phone', $body) && $body['phone'] !== '' && $body['phone'] !== null) {
        // Le front envoie "phone" : on le mappe sur la colonne Tel (nouvelle structure)
        $tel = (string)$body['phone'];
    }

    try {
        $st = $pdo->prepare('INSERT INTO users (full_name, email, Tel) VALUES (?, ?, ?)');
        $st->execute([(string)$body['full_name'], $email, $tel]);
        api_send_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
    } catch (PDOException $e) {
        // Gestion des doublons (Tel unique / email unique)
        if ($e->getCode() === '23000' || $e->getCode() === '1062') {
            if ($tel !== null) {
                $st2 = $pdo->prepare('SELECT id FROM users WHERE Tel = ?');
                $st2->execute([$tel]);
                $existing = $st2->fetch();
                if ($existing && isset($existing['id'])) {
                    $st3 = $pdo->prepare('UPDATE users SET full_name = COALESCE(?, full_name), email = COALESCE(?, email), Tel = COALESCE(?, Tel) WHERE id = ?');
                    $st3->execute([(string)$body['full_name'], $email, $tel, (int)$existing['id']]);
                    api_send_json(['ok' => true, 'id' => (int)$existing['id']], 200);
                }
            }

            if ($email !== null) {
                $st2 = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $st2->execute([$email]);
                $existing = $st2->fetch();
                if ($existing && isset($existing['id'])) {
                    $st3 = $pdo->prepare('UPDATE users SET full_name = COALESCE(?, full_name), email = COALESCE(?, email), Tel = COALESCE(?, Tel) WHERE id = ?');
                    $st3->execute([(string)$body['full_name'], $email, $tel, (int)$existing['id']]);
                    api_send_json(['ok' => true, 'id' => (int)$existing['id']], 200);
                }
            }
        }

        $debug = api_env('APP_DEBUG', '0');
        if ($debug === '1' || strtolower($debug) === 'true') {
            api_fail('Failed to create user', 500, ['detail' => $e->getMessage(), 'code' => $e->getCode()]);
        }
        api_fail('Failed to create user', 500);
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

    foreach (['full_name', 'email', 'Tel'] as $k) {
        if (array_key_exists($k, $body)) {
            $fields[] = "$k = ?";
            $v = $body[$k];
            $params[] = ($v === '' ? null : $v);
        }
    }

    if (!$fields) {
        api_fail('No fields to update', 422);
    }

    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
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

    $st = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $st->execute([$id]);

    api_send_json(['ok' => true]);
}

api_fail('Method not allowed', 405);
