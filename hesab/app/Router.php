<?php
declare(strict_types=1);

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET:' . $path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST:' . $path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $key = $method . ':' . $path;
        if (!isset($this->routes[$key])) {
            http_response_code(404);
            echo 'صفحه پیدا نشد';
            return;
        }
        ($this->routes[$key])();
    }
}
