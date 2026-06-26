<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\RoleController;
use App\Controllers\WebsiteController;
use App\Core\Application;
use App\Middleware\AuthMiddleware;
use App\Middleware\SuperAdminMiddleware;

$router = Application::getInstance()->router();

$router->get('/health', [HealthController::class, 'index']);

$router->group('api/v1', [], function ($router) {

    // Auth
    $router->group('auth', [], function ($router) {
        $router->post('login',   [AuthController::class, 'login']);
        $router->post('refresh', [AuthController::class, 'refresh']);
        $router->post('logout',  [AuthController::class, 'logout']);
        $router->get('me',       [AuthController::class, 'me'], [AuthMiddleware::class]);
    });

    // Roles & Permissions — super admin only
    $router->group('roles', ['middleware' => [AuthMiddleware::class, SuperAdminMiddleware::class]], function ($router) {
        $router->get('/',                [RoleController::class, 'index']);
        $router->get('{id}',             [RoleController::class, 'show']);
        $router->put('{id}/permissions', [RoleController::class, 'syncPermissions']);
    });

    $router->get(
        'permissions',
        [RoleController::class, 'permissions'],
        [AuthMiddleware::class, SuperAdminMiddleware::class],
    );

    // Websites — super admin only
    $router->group('websites', ['middleware' => [AuthMiddleware::class, SuperAdminMiddleware::class]], function ($router) {
        $router->get('/',      [WebsiteController::class, 'index']);
        $router->post('/',     [WebsiteController::class, 'store']);
        $router->get('{id}',   [WebsiteController::class, 'show']);
        $router->put('{id}',   [WebsiteController::class, 'update']);
        $router->delete('{id}',[WebsiteController::class, 'destroy']);
    });

});
