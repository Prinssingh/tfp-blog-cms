<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Core\Application;
use App\Middleware\AuthMiddleware;

$router = Application::getInstance()->router();

$router->get('/health', [HealthController::class, 'index']);

$router->group('api/v1', [], function ($router) {

    $router->group('auth', [], function ($router) {
        $router->post('login',   [AuthController::class, 'login']);
        $router->post('refresh', [AuthController::class, 'refresh']);
        $router->post('logout',  [AuthController::class, 'logout']);
        $router->get('me',       [AuthController::class, 'me'], [AuthMiddleware::class]);
    });

});
