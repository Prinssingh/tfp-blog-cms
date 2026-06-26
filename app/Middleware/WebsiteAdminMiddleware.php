<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Policies\Policy;

class WebsiteAdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        Policy::requireRole(
            $request->param('_auth'),
            'super_admin',
            'website_admin',
        );
        return $next($request);
    }
}
