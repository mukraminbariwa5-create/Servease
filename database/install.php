<?php
/**
 * Command-line installer:  php database/install.php [--no-seed]
 * (Same as visiting http://localhost/Servease/install)
 */
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$seed = !in_array('--no-seed', $argv, true);
$result = InstallController::install($seed);
echo "Servease installed" . ($seed ? " with demo data ({$result['users']} users, password: {$result['password']})" : '') . ".\n";
