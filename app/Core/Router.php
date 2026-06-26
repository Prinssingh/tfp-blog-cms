<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $options, callable $callback): void
    {
        $previousPrefix     = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix     = $previousPrefix . '/' . trim($prefix, '/');
        $this->groupMiddleware = array_merge($previousMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix     = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $fullPath   = $this->groupPrefix . '/' . trim($path, '/');
        $fullPath   = '/' . trim($fullPath, '/');
        $middleware = array_merge($this->groupMiddleware, $middleware);

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'pattern'    => $this->buildPattern($fullPath),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): Response
    {
        $this->handleCors($request);

        if ($request->method() === 'OPTIONS') {
            return Response::json([], 204);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (!preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            return $this->runMiddleware($request, $route['middleware'], function () use ($route, $request) {
                return $this->callHandler($route['handler'], $request);
            });
        }

        throw new NotFoundException('Route not found.');
    }

    private function runMiddleware(Request $request, array $middleware, callable $final): Response
    {
        if (empty($middleware)) {
            return $final();
        }

        $middlewareClass = array_shift($middleware);
        $instance = new $middlewareClass();

        return $instance->handle($request, function () use ($request, $middleware, $final) {
            return $this->runMiddleware($request, $middleware, $final);
        });
    }

    private function callHandler(array|callable $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            return $handler($request);
        }

        [$class, $method] = $handler;
        $controller = new $class();
        return $controller->$method($request);
    }

    private function handleCors(Request $request): void
    {
        $origin = $request->header('origin', '');

        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
}
