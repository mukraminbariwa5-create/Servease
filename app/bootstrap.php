<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

// ---- Configuration -------------------------------------------------------
$config = require ROOT . '/config/config.example.php';
if (is_file(ROOT . '/config/config.php')) {
    $config = array_replace_recursive($config, require ROOT . '/config/config.php');
}
$GLOBALS['__config'] = $config;

require APP . '/helpers.php';
require APP . '/icons.php';

date_default_timezone_set(config('app.timezone', 'Asia/Manila'));
mb_internal_encoding('UTF-8');

// ---- Errors --------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php-error.log');

// ---- Autoload core + controllers ----------------------------------------
spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

// ---- Session -------------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('fixoria_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() ?: '/',
        'httponly' => true,
        'secure'   => is_https(),
        'samesite' => 'Lax',
    ]);
    session_start();

    // Security headers
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// ---- Friendly error page for uncaught exceptions ---------------------------
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, (string) $e . PHP_EOL); exit(1); }
    http_response_code(500);
    if ($e instanceof PDOException && !DB::isInstalled(true)) {
        header('Location: ' . url('/install'));
        exit;
    }
    if (wants_json()) { json_out(['error' => 'Server error'], 500); }
    $debug = config('app.debug') ? $e : null;
    echo View::render('errors/500', ['exception' => $debug], 'layouts/blank');
});
