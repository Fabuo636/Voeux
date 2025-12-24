<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $st = $pdo->prepare('SELECT * FROM messages WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }
        api_send_json(['ok' => true, 'data' => $row]);
    }

    if (isset($_GET['public_code'])) {
        $code = (string)$_GET['public_code'];
        $st = $pdo->prepare('SELECT * FROM messages WHERE public_code = ?');
        $st->execute([$code]);
        $row = $st->fetch();
        if (!$row) {
            api_fail('Not found', 404);
        }

        $st2 = $pdo->prepare('INSERT INTO message_access_logs (message_id, channel, ip_hash, user_agent) VALUES (?, ?, ?, ?)');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            $st2->execute([(int)$row['id'], 'link', $ipHash, $ua]);
        } catch (Throwable $e) {
        }

        api_send_json(['ok' => true, 'data' => $row]);
    }

    api_fail('Missing id or public_code', 422);
}

if ($method === 'POST') {
    $body = api_read_json_body();
    api_required($body, ['request_id']);

    $requestId = (int)$body['request_id'];

    $chosenCandidateId = array_key_exists('chosen_candidate_id', $body) && $body['chosen_candidate_id'] !== '' ? (int)$body['chosen_candidate_id'] : null;
    $finalFr = array_key_exists('final_content_fr', $body) ? ($body['final_content_fr'] === '' ? null : (string)$body['final_content_fr']) : null;
    $finalEn = array_key_exists('final_content_en', $body) ? ($body['final_content_en'] === '' ? null : (string)$body['final_content_en']) : null;
    $background = array_key_exists('background', $body) ? ($body['background'] === '' ? null : (string)$body['background']) : null;

    if ($chosenCandidateId === null && ($finalFr === null && $finalEn === null)) {
        api_fail('Provide chosen_candidate_id or final_content_fr/final_content_en', 422);
    }

    if ($chosenCandidateId !== null && ($finalFr === null && $finalEn === null)) {
        $st = $pdo->prepare('SELECT content_fr, content_en FROM message_candidates WHERE id = ? AND request_id = ?');
        $st->execute([$chosenCandidateId, $requestId]);
        $cand = $st->fetch();
        if (!$cand) {
            api_fail('Candidate not found for this request', 404);
        }
        $finalFr = $cand['content_fr'];
        $finalEn = $cand['content_en'];
    }

    $publicCode = api_random_code(16);

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('INSERT INTO messages (request_id, chosen_candidate_id, final_content_fr, final_content_en, public_code, background) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$requestId, $chosenCandidateId, $finalFr, $finalEn, $publicCode, $background]);
        $messageId = (int)$pdo->lastInsertId();

        $st2 = $pdo->prepare('UPDATE message_requests SET status = ? WHERE id = ?');
        $st2->execute(['chosen', $requestId]);

        $pdo->commit();

        $frontBase = rtrim(api_env('PUBLIC_FRONT_BASE_URL', 'https://mutzig.cm/externe/Voeux/Front'), '/');

        $apiBase = rtrim(api_env('PUBLIC_API_BASE_URL', 'https://mutzig.cm/externe/Voeux/API'), '/');
        if ($apiBase === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $apiPath = rtrim(dirname($script), '/');
            $apiBase = ($host !== '') ? ($scheme . '://' . $host . $apiPath) : '';
        }

        if ($frontBase === '' && $apiBase !== '' && preg_match('~/API$~', $apiBase)) {
            $frontBase = preg_replace('~/API$~', '/Front', $apiBase);
        }

        if ($frontBase === '' && $apiBase !== '') {
            $root = rtrim(dirname($apiBase), '/');
            $frontBase = $root . '/Front';
        }

        $shareTpl = api_env('PUBLIC_SHARE_URL_TEMPLATE', '');
        if ($shareTpl !== '') {
            $publicUrl = str_replace('{code}', urlencode($publicCode), $shareTpl);
        } else {
            $publicUrl = 'https://mutzig.cm/happynewyear2026?i=' . urlencode($publicCode);
        }

        api_send_json(['ok' => true, 'id' => $messageId, 'public_code' => $publicCode, 'public_url' => $publicUrl], 201);
    } catch (Throwable $e) {
        $pdo->rollBack();
        api_fail('Failed to save message', 500);
    }
}

api_fail('Method not allowed', 405);
