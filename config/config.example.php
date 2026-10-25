<?php
/**
 * Servease configuration.
 * Copy this file to config.php and adjust. config.php is loaded automatically;
 * if it does not exist, these defaults are used (XAMPP defaults).
 */
return [
    'app' => [
        'name'      => 'Servease',
        // 'local' shows helpful links (verification/reset) on screen and enables the installer.
        // Set to 'production' when deploying.
        'env'       => 'local',
        'debug'     => true,
        // Leave empty to auto-detect (e.g. http://localhost/Servease)
        'url'       => '',
        'timezone'  => 'Asia/Manila',
        'currency'  => '₱',
        // Shows one-click demo accounts on the sign-in page
        'demo'      => true,
        // Public portfolio demo: protects the demo accounts, turns uploads off and puts the demo
        // data back on the first visit of each day. Only for a public demo server (see DEPLOY.md).
        'public_demo'      => false,
        'demo_reset_daily' => true,
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'servease',
        'user'    => 'root',
        'pass'    => '',
    ],

    'mail' => [
        // 'log'  -> emails are written to storage/logs/mail.log (recommended locally)
        // 'mail' -> PHP mail() (requires SMTP set up in php.ini)
        'driver'     => 'log',
        'from_email' => 'no-reply@servease.test',
        'from_name'  => 'Servease',
    ],

    'payments' => [
        // Online payments through PayMongo (GCash, Maya, cards, QR Ph).
        // Off by default: bookings are paid directly to the provider (cash / GCash / Maya / bank)
        // and recorded in Servease. To enable, create a PayMongo account and paste your TEST keys.
        'paymongo' => [
            'enabled'        => false,
            'secret_key'     => '',   // sk_test_...
            'webhook_secret' => '',   // whsk_... (optional; payment is also verified on return)
            'methods'        => ['gcash', 'paymaya', 'card', 'qrph'],
        ],
        // Platform service fee shown to clients (0 = none). Informational only.
        'service_fee_percent' => 0,
    ],

    'uploads' => [
        'max_image_mb' => 5,
        'max_doc_mb'   => 8,
    ],
];
