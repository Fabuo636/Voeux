<?php

declare(strict_types=1);

require_once __DIR__ . '/lib.php';

api_handle_cors_preflight();

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

if ($frontBase === '') {
    api_fail('Front base URL is not configured', 500);
}

$target = $frontBase . '/voeux.html?code=' . urlencode($code);

header('Access-Control-Allow-Origin: *');
header('Location: ' . $target, true, 302);
exit;
