<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private array $headers = [];

    private function __construct(
        private readonly mixed $body,
        private readonly int $status,
    ) {}

    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self($data, $status);
        $response->headers['Content-Type'] = 'application/json; charset=utf-8';
        return $response;
    }

    public static function success(mixed $data = [], string $message = 'Operation successful', int $status = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function created(mixed $data = [], string $message = 'Resource created.'): self
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public static function paginated(array $items, int $total, int $page, int $limit, string $message = 'Operation successful'): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => [
                'page'      => $page,
                'limit'     => $limit,
                'total'     => $total,
                'last_page' => (int) ceil($total / max($limit, 1)),
            ],
        ]);
    }

    public function withHeader(string $key, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$key] = $value;
        return $clone;
    }

    public function send(): never
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if ($this->status !== 204 && $this->body !== '') {
            echo is_string($this->body) ? $this->body : json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        exit;
    }
}
