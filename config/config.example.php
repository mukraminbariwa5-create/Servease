<?php
/**
 * Fixoria configuration.
 * Copy this file to config.php and adjust. config.php is loaded automatically;
 * if it does not exist, these defaults are used (XAMPP defaults).
 */
return [
    'app' => [
        'name'      => 'Fixoria',
        // 'local' shows helpful links (verification/reset) on screen and enables the installer.
        // Set to 'production' when deploying.
        'env'       => 'local',
        'debug'     => true,
        // Leave empty to auto-detect (e.g. http://localhost/Fixoria)
        'url'       => '',
        'timezone'  => 'Asia/Manila',
        'currency'  => '₱',
        // Colour theme: 'blue' (Trusted Blue & Yellow), 'green' (Fresh Green & Orange)
        // or 'navy' (Workwear Navy & Orange). Visitors can also switch light/dark mode.
        'theme'     => 'blue',
        // Shows one-click demo accounts on the sign-in page
        'demo'      => true,
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'fixoria',
        'user'    => 'root',
        'pass'    => '',
    ],

    'mail' => [
        // 'log'  -> emails are written to storage/logs/mail.log (recommended locally)
        // 'mail' -> PHP mail() (requires SMTP set up in php.ini)
        'driver'     => 'log',
        'from_email' => 'no-reply@fixoria.test',
        'from_name'  => 'Fixoria',
    ],

    'payments' => [
        // Online payments through PayMongo (GCash, Maya, cards, QR Ph).
        // Off by default: bookings are paid directly to the provider (cash / GCash / Maya / bank)
        // and recorded in Fixoria. To enable, create a PayMongo account and paste your TEST keys.
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
