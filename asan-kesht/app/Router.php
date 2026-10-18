<?php

declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method:string,path:string,handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $path): bool
    {
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            if (!is_string($pattern)) {
                continue;
            }
            if (!preg_match('#^' . $pattern . '$#', $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            ($route['handler'])($params);
            return true;
        }
        return false;
    }
}
