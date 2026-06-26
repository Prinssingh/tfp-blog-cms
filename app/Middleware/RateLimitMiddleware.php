<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $cacheDir;

    public function __construct(int $maxRequests = 500, int $windowSeconds = 3600)
    {
        $this->maxRequests   = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->cacheDir      = BASE_PATH . '/storage/cache/ratelimit';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function handle(Request $request, callable $next): Response
    {
        $key  = md5($request->ip());
        $file = $this->cacheDir . '/' . $key . '.json';
        $now  = time();

        $data = ['count' => 0, 'window_start' => $now];

        if (file_exists($file)) {
            $stored = json_decode(file_get_contents($file), true);
            if ($stored && ($now - $stored['window_start']) < $this->windowSeconds) {
                $data = $stored;
            }
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > $this->maxRequests) {
            throw new AppException('Too many requests. Please try again later.', 429);
        }

        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $data['count']))
            ->withHeader('X-RateLimit-Reset', (string) ($data['window_start'] + $this->windowSeconds));
    }
}
