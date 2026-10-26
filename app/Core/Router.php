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
        if ($allowed && $method === 'GET') self::recoverLostForm($path);
        abort($allowed ? 405 : 404);
    }

    /**
     * A GET for a form-only address means the form's POST never reached the app. Free hosts
     * such as InfinityFree run a browser check that can catch a form submit and replay it as
     * a plain GET (adding ?i=1), which drops the form data. Instead of an error page, send the
     * visitor back with a short note so they can press the button again.
     */
    private static function recoverLostForm(string $path): void
    {
        $sameSite = ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'same-origin';
        if ((rtrim($path, '/') === '/logout') && $sameSite) {
            Auth::logout();
            flash('success', 'You have been signed out.');
            redirect('/login');
        }
        flash('error', "That didn't go through. Please try again.");
        // Go back to the page the form was on, but never to this same address (that would loop)
        $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $refPath = (string)parse_url($ref, PHP_URL_PATH);
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host && rtrim($refPath, '/') !== rtrim(base_path() . $path, '/')) {
            header('Location: ' . $ref, true, 303);
            exit;
        }
        redirect(Auth::user() ? '/account' : '/login');
    }
}
