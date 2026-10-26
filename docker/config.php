<?php
/**
 * Settings for the public demo container (docker/README.md). Nothing secret here:
 * the database only listens inside the container, and no email is sent.
 */
return [
    'app' => [
        'env'         => 'production',
        'debug'       => false,
        'demo'        => true,
        'public_demo' => true,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'servease',
        'user' => 'servease',
        'pass' => 'servease-demo',
    ],
    'mail' => ['driver' => 'log'],
];
