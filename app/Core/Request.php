<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $body = [];
    private array $query = [];
    private array $params = [];
    private array $headers = [];

    private function __construct(
        private readonly string $method,
        private readonly string $path,
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path   = '/' . trim($path, '/');

        $request = new self($method, $path);

        $request->query = $_GET ?? [];

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $request->body = json_decode($raw, true) ?? [];
            }
        }

        foreach (getallheaders() as $name => $value) {
            $request->headers[strtolower($name)] = $value;
        }

        return $request;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    public function isJson(): bool
    {
        return str_contains($this->header('content-type', ''), 'application/json');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }
}
