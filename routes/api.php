<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\HealthController;
use App\Controllers\RoleController;
use App\Controllers\TagController;
use App\Controllers\UserController;
use App\Controllers\WebsiteController;
use App\Core\Application;
use App\Middleware\AuthMiddleware;
use App\Middleware\SuperAdminMiddleware;
use App\Middleware\WebsiteAdminMiddleware;

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
        $router->get('/',       [WebsiteController::class, 'index']);
        $router->post('/',      [WebsiteController::class, 'store']);
        $router->get('{id}',    [WebsiteController::class, 'show']);
        $router->put('{id}',    [WebsiteController::class, 'update']);
        $router->delete('{id}', [WebsiteController::class, 'destroy']);
    });

    // Users — website admin and above
    $router->group('users', ['middleware' => [AuthMiddleware::class, WebsiteAdminMiddleware::class]], function ($router) {
        $router->get('/',       [UserController::class, 'index']);
        $router->post('/',      [UserController::class, 'store']);
        $router->get('{id}',    [UserController::class, 'show']);
        $router->put('{id}',    [UserController::class, 'update']);
        $router->delete('{id}', [UserController::class, 'destroy']);
    });

    // Categories — editor and above
    $router->group('categories', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/',       [CategoryController::class, 'index']);
        $router->post('/',      [CategoryController::class, 'store']);
        $router->get('{id}',    [CategoryController::class, 'show']);
        $router->put('{id}',    [CategoryController::class, 'update']);
        $router->delete('{id}', [CategoryController::class, 'destroy']);
    });

    // Tags — editor and above
    $router->group('tags', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/',       [TagController::class, 'index']);
        $router->post('/',      [TagController::class, 'store']);
        $router->get('{id}',    [TagController::class, 'show']);
        $router->put('{id}',    [TagController::class, 'update']);
        $router->delete('{id}', [TagController::class, 'destroy']);
    });

});
