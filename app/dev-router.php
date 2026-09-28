<?php
// Router for PHP's built-in server (development/testing only):
//   php -S localhost:8000 app/dev-router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__ . '/..' . $path);
$root = realpath(__DIR__ . '/..');
if ($path !== '/' && $file && str_starts_with($file, $root) && is_file($file)
    && !preg_match('#^/(app|config|database|storage|tests)/#', $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../index.php';
