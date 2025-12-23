<?php

declare(strict_types=1);

function api_send_json($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

    if ($status === 204) {
        exit;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_fail(string $message, int $status = 400, array $extra = []): void
{
    api_send_json(array_merge(['ok' => false, 'error' => $message], $extra), $status);
}

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_fail('Invalid JSON body', 400);
    }

    return $data;
}

function api_handle_cors_preflight(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        api_send_json(null, 204);
    }
}

function api_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }

    return $default;
}

function api_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = api_env('DB_HOST', '37.187.28.103');
    $port = api_env('DB_PORT', '');
    $socket = api_env('DB_SOCKET', '');
    $db = api_env('DB_NAME', 'carte_veux');
    $user = api_env('DB_USER', 'carte_veux_user');
    $pass = api_env('DB_PASS', 'p*bKY9v5bDuh5hj@');
    $charset = 'utf8mb4';

    if ($socket !== '') {
        $dsn = "mysql:unix_socket={$socket};dbname={$db};charset={$charset}";
    } else {
        $portPart = $port !== '' ? ";port={$port}" : '';
        $dsn = "mysql:host={$host}{$portPart};dbname={$db};charset={$charset}";
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        $debug = api_env('APP_DEBUG', '0');
        if ($debug === '1' || strtolower($debug) === 'true') {
            api_fail('Database connection failed', 500, ['detail' => $e->getMessage()]);
        }
        api_fail('Database connection failed', 500);
    }

    return $pdo;
}

function api_required(array $data, array $keys): void
{
    foreach ($keys as $k) {
        if (!array_key_exists($k, $data) || $data[$k] === null || $data[$k] === '') {
            api_fail('Missing field: ' . $k, 422);
        }
    }
}

function api_random_code(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}
