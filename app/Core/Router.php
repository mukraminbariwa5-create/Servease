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

    public function dispatch(string $method, string $path): void
    {
        if (!DB::isInstalled() && !str_starts_with($path, '/install')) {
            redirect('/install');
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
