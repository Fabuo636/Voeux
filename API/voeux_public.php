<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$pdo = api_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    api_fail('Method not allowed', 405);
}

$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
if ($code === '' && isset($_GET['i'])) {
    $code = trim((string)$_GET['i']);
}
if ($code === '') {
    api_fail('Missing code', 422);
}

$st = $pdo->prepare(
    'SELECT
        m.id,
        m.public_code,
        m.final_content_fr,
        m.background,
        m.expires_at,
        m.created_at,
        r.full_name AS recipient_name,
        r.gender AS recipient_gender,
        u.full_name AS sender_name
     FROM messages m
     LEFT JOIN message_requests mr ON mr.id = m.request_id
     LEFT JOIN users u ON u.id = mr.user_id
     LEFT JOIN recipients r ON r.id = mr.recipient_id
     WHERE m.public_code = ?'
);
$st->execute([$code]);
$row = $st->fetch();
if (!$row) {
    api_fail('Not found', 404);
}

if ($row['expires_at'] !== null) {
    try {
        $exp = new DateTimeImmutable((string)$row['expires_at']);
        $now = new DateTimeImmutable('now');
        if ($exp <= $now) {
            api_fail('Expired', 410);
        }
    } catch (Throwable $e) {
    }
}

$st2 = $pdo->prepare('INSERT INTO message_access_logs (message_id, channel, ip_hash, user_agent) VALUES (?, ?, ?, ?)');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ipHash = $ip !== '' ? hash('sha256', $ip) : null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
try {
    $st2->execute([(int)$row['id'], 'public', $ipHash, $ua]);
} catch (Throwable $e) {
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

api_send_json([
    'ok' => true,
    'data' => [
        'code' => (string)$row['public_code'],
        'message' => $row['final_content_fr'] === null ? '' : (string)$row['final_content_fr'],
        'background' => $row['background'] === null ? null : (string)$row['background'],
        'recipient_name' => $row['recipient_name'] === null ? null : (string)$row['recipient_name'],
        'recipient_gender' => $row['recipient_gender'] === null ? null : (string)$row['recipient_gender'],
        'sender_name' => $row['sender_name'] === null ? null : (string)$row['sender_name'],
        'created_at' => (string)$row['created_at'],
    ],
]);
