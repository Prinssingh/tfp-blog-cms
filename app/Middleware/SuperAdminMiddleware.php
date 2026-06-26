<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Policies\Policy;

class SuperAdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        Policy::requireSuperAdmin($request->param('_auth'));
        return $next($request);
    }
}
