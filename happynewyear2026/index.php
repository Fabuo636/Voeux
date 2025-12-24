<?php

declare(strict_types=1);

$code = isset($_GET['i']) ? trim((string)$_GET['i']) : '';
if ($code === '' && isset($_GET['code'])) {
    $code = trim((string)$_GET['code']);
}

$target = '/externe/Voeux/Front/voeux.html';
if ($code !== '') {
    $target .= '?i=' . urlencode($code);
}

header('Location: ' . $target, true, 302);
exit;
