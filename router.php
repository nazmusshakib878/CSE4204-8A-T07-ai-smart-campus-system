<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$publicDir = __DIR__ . '/backend/public';
$requestedFile = $publicDir . $uri;

if (str_starts_with($uri, '/api')) {
    require $publicDir . '/index.php';
    return true;
}

if ($uri !== '/' && is_file($requestedFile)) {
    return false;
}

$frontendIndex = $publicDir . '/index.html';
if (is_file($frontendIndex)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($frontendIndex);
    return true;
}

require $publicDir . '/index.php';
return true;
