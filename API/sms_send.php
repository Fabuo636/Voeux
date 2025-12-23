<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    api_fail('Method not allowed', 405);
}

$body = api_read_json_body();
api_required($body, ['mobiles', 'sms']);

$mobiles = trim((string)$body['mobiles']);
$sms = trim((string)$body['sms']);
$senderid = array_key_exists('senderid', $body) && $body['senderid'] !== '' ? (string)$body['senderid'] : api_env('SMS_SENDERID', 'MUTZIG');
$scheduletime = array_key_exists('scheduletime', $body) ? (string)$body['scheduletime'] : '';

$apiKey = api_env('SMS_API_KEY', 'f9c8b1e7a1c44f5b9d6c3e2a8f7c0b11');
if ($apiKey === '') {
    api_fail('SMS_API_KEY is not configured on server', 500);
}

$url = 'https://advancedsmssending.zen-apps.com/sms_gateway.php';

$payload = json_encode([
    'senderid' => $senderid,
    'sms' => $sms,
    'mobiles' => $mobiles,
    'scheduletime' => $scheduletime,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($payload === false) {
    api_fail('Failed to encode payload', 500);
}

$ch = curl_init($url);
if ($ch === false) {
    api_fail('Failed to init curl', 500);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-KEY: ' . $apiKey,
    ],
    CURLOPT_TIMEOUT => 20,
]);

$responseBody = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    api_fail('SMS gateway request failed: ' . $curlErr, 502);
}

$decoded = json_decode((string)$responseBody, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $msg = is_array($decoded) ? ($decoded['error'] ?? $decoded['message'] ?? 'Gateway error') : (string)$responseBody;
    api_fail('SMS gateway error: ' . $msg, 502, ['gateway_http' => $httpCode]);
}

api_send_json([
    'ok' => true,
    'gateway_http' => $httpCode,
    'data' => is_array($decoded) ? $decoded : ['raw' => (string)$responseBody],
]);
