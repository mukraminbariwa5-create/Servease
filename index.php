<?php
/**
 * Servease — front controller.
 * Every request that is not a real file is routed through here (see .htaccess).
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new Router();
require __DIR__ . '/app/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
