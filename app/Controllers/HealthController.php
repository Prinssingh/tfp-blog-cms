<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class HealthController
{
    public function index(Request $request): Response
    {
        $dbStatus = 'ok';

        try {
            Database::connection()->query('SELECT 1');
        } catch (\Throwable) {
            $dbStatus = 'error';
        }

        return Response::json([
            'success' => true,
            'message' => 'TFP Blog CMS API is running.',
            'data'    => [
                'app'      => $_ENV['APP_NAME'] ?? 'TFP Blog CMS',
                'version'  => 'v1.0.0',
                'env'      => $_ENV['APP_ENV'] ?? 'production',
                'php'      => PHP_VERSION,
                'database' => $dbStatus,
                'time'     => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
