<?php
declare(strict_types=1);

/**
 * Makes Servease installable as an app (a Progressive Web App) while it stays a normal website.
 *  - /manifest.webmanifest  app name, icons, colours, start page and home-screen shortcuts
 *  - /sw.js                 service worker: caches the app shell, shows an offline screen
 *  - /offline               the screen shown when there's no connection
 * All three are built here so they work in any folder (e.g. http://localhost/Servease).
 */
final class AppController
{
    /** Bump when the service worker's caching rules change. */
    private const SW_VERSION = 5;

    public function manifest(): void
    {
        $base = base_path() . '/';
        $icon = fn(string $f) => url('assets/img/' . $f);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: no-cache');
        echo json_encode([
            'id'               => $base,
            'name'             => 'Servease: Home services',
            'short_name'       => 'Servease',
            'description'      => 'Book trusted, ID-verified pros for aircon, cleaning, plumbing, electrical work and more.',
            'lang'             => 'en-PH',
            'start_url'        => $base . '?source=app',
            'scope'            => $base,
            'display'          => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation'      => 'portrait',
            'background_color' => '#ffffff',
            'theme_color'      => '#1d4ed8',
            'categories'       => ['lifestyle', 'productivity', 'business'],
            'icons' => [
                ['src' => $icon('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'Find a service', 'short_name' => 'Services', 'url' => $base . 'categories', 'icons' => [['src' => $icon('icon-192.png'), 'sizes' => '192x192']]],
                ['name' => 'My bookings', 'short_name' => 'Bookings', 'url' => $base . 'bookings', 'icons' => [['src' => $icon('icon-192.png'), 'sizes' => '192x192']]],
                ['name' => 'Inbox', 'url' => $base . 'messages', 'icons' => [['src' => $icon('icon-192.png'), 'sizes' => '192x192']]],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function serviceWorker(): void
    {
        $base = base_path();
        // The cache name changes whenever the stylesheet or script changes, so updates reach installed apps
        $stamp = self::SW_VERSION . '-' . substr(md5(
            (string) @filemtime(ROOT . '/assets/css/app.css') . (string) @filemtime(ROOT . '/assets/js/app.js')
        ), 0, 8);
        $shell = [
            url('/offline'),
            asset('css/app.css'), asset('js/app.js'),
            url('/assets/fonts/archivo-variable.woff2'),
            asset('img/favicon.svg'), url('/assets/img/icon-192.png'),
        ];
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Service-Worker-Allowed: ' . $base . '/');
        $js = file_get_contents(APP . '/Views/app/sw.js');
        echo strtr($js, [
            '__CACHE__' => 'servease-' . $stamp,
            '__BASE__'  => $base,
            '__SHELL__' => json_encode($shell, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function offline(): void
    {
        view('app/offline', ['title' => 'You\'re offline'], 'layouts/blank');
    }
}
