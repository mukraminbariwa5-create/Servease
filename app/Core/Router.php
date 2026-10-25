<?php
declare(strict_types=1);

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void  { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, array $handler): void { $this->add('POST', $pattern, $handler); }

    private function add(string $method, string $pattern, array $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/') ?: '/') . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    /** Set once a visitor has seen the intro slides. */
    public const INTRO_COOKIE = 'se_intro';

    /** Pages a signed-out visitor can open: the intro, sign-in and sign-up, password help, the app files, setup and webhooks. */
    private const PUBLIC = ['/welcome', '/login', '/register', '/forgot-password', '/reset-password', '/verify-email', '/logout',
                            '/manifest.webmanifest', '/sw.js', '/offline', '/lang'];

    private static function isPublic(string $path): bool
    {
        if (in_array(rtrim($path, '/') ?: '/', self::PUBLIC, true)) return true;
        return str_starts_with($path, '/install') || str_starts_with($path, '/webhooks/');
    }

    public function dispatch(string $method, string $path): void
    {
        if ($method === 'HEAD') $method = 'GET';  // HEAD gets the same answer (PHP drops the body)
        if (!DB::isInstalled() && !str_starts_with($path, '/install')) {
            redirect('/install');
        }
        if (!str_starts_with($path, '/install')) { Migrator::run(); Reminders::runIfDue(); }

        // Servease is an app: every page needs an account. Guests see the intro once, then the login screen.
        if (!Auth::user() && !self::isPublic($path)) {
            if ($method === 'GET' && $path !== '/') $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect(empty($_COOKIE[self::INTRO_COOKIE]) ? '/welcome' : '/login');
        }

        $allowed = false;
        foreach ($this->routes as [$m, $regex, $handler]) {
            if (!preg_match($regex, $path, $matches)) continue;
            if ($m !== $method) { $allowed = true; continue; }

            if ($method === 'POST' && !str_starts_with($path, '/webhooks/')) verify_csrf();

            $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = $handler;
            (new $class())->$action(...$params);
            return;
        }
        abort($allowed ? 405 : 404);
    }
}
