<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class HealthController
{
    public function index(Request $request): Response
    {
        $checks = [];

        // Database
        try {
            Database::connection()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
        }

        // Cache writable
        try {
            Cache::set('_health_check', true, 10);
            $checks['cache'] = Cache::get('_health_check') === true ? 'ok' : 'error';
            Cache::forget('_health_check');
        } catch (\Throwable) {
            $checks['cache'] = 'error';
        }

        // Uploads writable
        $uploadsDir = defined('BASE_PATH') ? BASE_PATH . '/uploads' : '';
        $checks['uploads'] = ($uploadsDir && is_writable($uploadsDir)) ? 'ok' : 'error';

        $allOk = !in_array('error', $checks, true);

        return Response::json([
            'success' => $allOk,
            'message' => $allOk ? 'All systems operational.' : 'One or more checks failed.',
            'data'    => [
                'app'     => $_ENV['APP_NAME'] ?? 'TFP Blog CMS',
                'version' => 'v1.0.0',
                'env'     => $_ENV['APP_ENV'] ?? 'production',
                'php'     => PHP_VERSION,
                'time'    => date('Y-m-d H:i:s'),
                'checks'  => $checks,
            ],
        ], $allOk ? 200 : 503);
    }
}
