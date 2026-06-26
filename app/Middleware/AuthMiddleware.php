<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\UnauthorizedException;
use App\Helpers\JwtHelper;
use Throwable;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new UnauthorizedException('Access token required.');
        }

        try {
            $payload = JwtHelper::decode($token);
        } catch (Throwable) {
            throw new UnauthorizedException('Invalid or expired token.');
        }

        if (isset($payload->type) && $payload->type === 'refresh') {
            throw new UnauthorizedException('Cannot use refresh token as access token.');
        }

        $request->setParams(['_auth' => $payload]);

        return $next($request);
    }
}
