<?php

declare(strict_types=1);

namespace HazeWatch\Http;

final class Router
{
    /** @var array<string, callable(): Response> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET ' . $this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $path): Response
    {
        $route = strtoupper($method) . ' ' . $this->normalizePath($path);
        if (!isset($this->routes[$route])) {
            return Response::html('<h1>404</h1><p>Page not found.</p>', 404);
        }

        return ($this->routes[$route])();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
