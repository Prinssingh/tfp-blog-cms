<?php

declare(strict_types=1);

use App\Core\Application;

$router = Application::getInstance()->router();

$router->group('api/v1/public', [], function ($router) {

    // Public post routes — Phase 10
    // $router->get('posts', [PublicPostController::class, 'index']);
    // $router->get('posts/{slug}', [PublicPostController::class, 'show']);
    // $router->get('categories', [PublicCategoryController::class, 'index']);
    // $router->get('tags', [PublicTagController::class, 'index']);
    // $router->get('authors', [PublicAuthorController::class, 'index']);
    // $router->get('search', [PublicSearchController::class, 'index']);

});
