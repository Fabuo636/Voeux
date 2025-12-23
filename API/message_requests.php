<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $st = $pdo->prepare('SELECT * FROM message_requests WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    $filters = [];
    $params = [];

    if (isset($_GET['user_id'])) {
        $filters[] = 'user_id = ?';
        $params[] = (int)$_GET['user_id'];
    }

    if (isset($_GET['recipient_id'])) {
        $filters[] = 'recipient_id = ?';
        $params[] = (int)$_GET['recipient_id'];
    }

    if (isset($_GET['status'])) {
        $filters[] = 'status = ?';
        $params[] = (string)$_GET['status'];
    }

    $where = $filters ? ('WHERE ' . implode(' AND ', $filters)) : '';
    $sql = 'SELECT * FROM message_requests ' . $where . ' ORDER BY created_at DESC';

    $st = $pdo->prepare($sql);
    $st->execute($params);

    api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $body = api_read_json_body();
    api_required($body, ['user_id', 'recipient_id']);

    $occasionId = array_key_exists('occasion_id', $body) && $body['occasion_id'] !== '' ? (int)$body['occasion_id'] : null;
    $relationshipId = array_key_exists('relationship_id', $body) && $body['relationship_id'] !== '' ? (int)$body['relationship_id'] : null;
    $messageTypeId = array_key_exists('message_type_id', $body) && $body['message_type_id'] !== '' ? (int)$body['message_type_id'] : null;

    $senderGender = array_key_exists('sender_gender', $body) && $body['sender_gender'] !== '' ? (string)$body['sender_gender'] : null;
    $recipientGender = array_key_exists('recipient_gender', $body) && $body['recipient_gender'] !== '' ? (string)$body['recipient_gender'] : null;

    $tone = array_key_exists('tone', $body) && $body['tone'] !== '' ? (string)$body['tone'] : null;

    $constraintsJson = null;
    if (array_key_exists('constraints', $body) && $body['constraints'] !== null) {
        if (is_array($body['constraints'])) {
            $constraintsJson = json_encode($body['constraints'], JSON_UNESCAPED_UNICODE);
        } elseif (is_string($body['constraints'])) {
            $constraintsJson = $body['constraints'];
        } else {
            api_fail('constraints must be object or string', 422);
        }
    }

    $outputLang = array_key_exists('output_lang', $body) && $body['output_lang'] !== '' ? (string)$body['output_lang'] : 'fr';

    $st = $pdo->prepare(
        'INSERT INTO message_requests (
            user_id, recipient_id, occasion_id, relationship_id, message_type_id,
            sender_gender, recipient_gender, tone, constraints, output_lang, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $st->execute([
        (int)$body['user_id'],
        (int)$body['recipient_id'],
        $occasionId,
        $relationshipId,
        $messageTypeId,
        $senderGender,
        $recipientGender,
        $tone,
        $constraintsJson,
        $outputLang,
        'draft',
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

    $intNullable = ['occasion_id', 'relationship_id', 'message_type_id'];
    foreach ($intNullable as $k) {
        if (array_key_exists($k, $body)) {
            $fields[] = "$k = ?";
            $params[] = ($body[$k] === '' || $body[$k] === null) ? null : (int)$body[$k];
        }
    }

    foreach (['sender_gender', 'recipient_gender', 'tone', 'output_lang', 'status'] as $k) {
        if (array_key_exists($k, $body)) {
            $fields[] = "$k = ?";
            $params[] = ($body[$k] === '' ? null : (string)$body[$k]);
        }
    }

    if (array_key_exists('constraints', $body)) {
        $fields[] = 'constraints = ?';
        if ($body['constraints'] === null || $body['constraints'] === '') {
            $params[] = null;
        } elseif (is_array($body['constraints'])) {
            $params[] = json_encode($body['constraints'], JSON_UNESCAPED_UNICODE);
        } elseif (is_string($body['constraints'])) {
            $params[] = $body['constraints'];
        } else {
            api_fail('constraints must be object or string', 422);
        }
    }

    if (!$fields) {
        api_fail('No fields to update', 422);
    }

    $params[] = $id;
    $sql = 'UPDATE message_requests SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $st = $pdo->prepare($sql);
    $st->execute($params);

    api_send_json(['ok' => true]);
}

api_fail('Method not allowed', 405);
