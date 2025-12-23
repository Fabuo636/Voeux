<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET') {
    if ($id) {
        $st = $pdo->prepare('SELECT * FROM message_candidates WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    if (!isset($_GET['request_id'])) {
        api_fail('Missing request_id', 422);
    }

    $requestId = (int)$_GET['request_id'];
    $st = $pdo->prepare('SELECT * FROM message_candidates WHERE request_id = ? ORDER BY variant_index ASC');
    $st->execute([$requestId]);
    api_send_json(['ok' => true, 'data' => $st->fetchAll()]);
}

if ($method === 'POST') {
    $body = api_read_json_body();

    if (isset($body['items']) && is_array($body['items'])) {
        api_required($body, ['request_id']);
        $requestId = (int)$body['request_id'];

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'INSERT INTO message_candidates (request_id, variant_index, content_fr, content_en, model) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE content_fr = VALUES(content_fr), content_en = VALUES(content_en), model = VALUES(model)'
            );

            foreach ($body['items'] as $it) {
                if (!is_array($it)) {
                    api_fail('items must be an array of objects', 422);
                }
                api_required($it, ['variant_index']);

                $variantIndex = (int)$it['variant_index'];
                $contentFr = array_key_exists('content_fr', $it) ? ($it['content_fr'] === '' ? null : (string)$it['content_fr']) : null;
                $contentEn = array_key_exists('content_en', $it) ? ($it['content_en'] === '' ? null : (string)$it['content_en']) : null;
                $model = array_key_exists('model', $it) ? ($it['model'] === '' ? null : (string)$it['model']) : null;

                $st->execute([$requestId, $variantIndex, $contentFr, $contentEn, $model]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            api_fail('Failed to save candidates', 500);
        }

        api_send_json(['ok' => true], 201);
    }

    api_required($body, ['request_id', 'variant_index']);

    $requestId = (int)$body['request_id'];
    $variantIndex = (int)$body['variant_index'];
    $contentFr = array_key_exists('content_fr', $body) ? ($body['content_fr'] === '' ? null : (string)$body['content_fr']) : null;
    $contentEn = array_key_exists('content_en', $body) ? ($body['content_en'] === '' ? null : (string)$body['content_en']) : null;
    $model = array_key_exists('model', $body) ? ($body['model'] === '' ? null : (string)$body['model']) : null;

    $st = $pdo->prepare(
        'INSERT INTO message_candidates (request_id, variant_index, content_fr, content_en, model)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE content_fr = VALUES(content_fr), content_en = VALUES(content_en), model = VALUES(model)'
    );

    $st->execute([$requestId, $variantIndex, $contentFr, $contentEn, $model]);

    api_send_json(['ok' => true], 201);
}

api_fail('Method not allowed', 405);
