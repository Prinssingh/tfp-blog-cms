<?php

declare(strict_types=1);

use App\Controllers\AnalyticsController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\HealthController;
use App\Controllers\MediaController;
use App\Controllers\PostController;
use App\Controllers\PublicController;
use App\Controllers\RedirectController;
use App\Controllers\RoleController;
use App\Controllers\SeoController;
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

    // Websites — super admin only (CRUD), website admin can read own website + manage settings
    $router->group('websites', ['middleware' => [AuthMiddleware::class, SuperAdminMiddleware::class]], function ($router) {
        $router->get('/',       [WebsiteController::class, 'index']);
        $router->post('/',      [WebsiteController::class, 'store']);
        $router->get('{id}',    [WebsiteController::class, 'show']);
        $router->put('{id}',    [WebsiteController::class, 'update']);
        $router->delete('{id}', [WebsiteController::class, 'destroy']);
    });

    // Website settings — website admin and above
    $router->group('websites', ['middleware' => [AuthMiddleware::class, WebsiteAdminMiddleware::class]], function ($router) {
        $router->get('{id}/settings', [WebsiteController::class, 'getSettings']);
        $router->put('{id}/settings', [WebsiteController::class, 'updateSettings']);
    });

    // Users — website admin and above
    $router->group('users', ['middleware' => [AuthMiddleware::class, WebsiteAdminMiddleware::class]], function ($router) {
        $router->get('/',                [UserController::class, 'index']);
        $router->post('/',               [UserController::class, 'store']);
        $router->get('{id}',             [UserController::class, 'show']);
        $router->put('{id}',             [UserController::class, 'update']);
        $router->delete('{id}',          [UserController::class, 'destroy']);
        $router->get('{id}/activity',    [UserController::class, 'activity']);
    });

    // Audit logs — website admin and above
    $router->get(
        'audit-logs',
        [AuditController::class, 'index'],
        [AuthMiddleware::class, WebsiteAdminMiddleware::class],
    );

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

    // Media
    $router->group('media', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/',       [MediaController::class, 'index']);
        $router->post('upload', [MediaController::class, 'upload']);
        $router->get('{id}',    [MediaController::class, 'show']);
        $router->put('{id}',    [MediaController::class, 'updateMeta']);
        $router->delete('{id}', [MediaController::class, 'destroy']);
    });

    // Posts
    $router->group('posts', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/',                                        [PostController::class, 'index']);
        $router->post('/',                                       [PostController::class, 'store']);
        $router->get('trash',                                    [PostController::class, 'trash']);
        $router->get('status-counts',                            [PostController::class, 'statusCounts']);
        $router->get('{id}',                                     [PostController::class, 'show']);
        $router->put('{id}',                                     [PostController::class, 'update']);
        $router->delete('{id}',                                  [PostController::class, 'destroy']);
        $router->delete('{id}/force',                            [PostController::class, 'forceDelete']);
        $router->post('{id}/restore',                            [PostController::class, 'restoreFromTrash']);
        $router->post('{id}/submit-review',                      [PostController::class, 'submitReview']);
        $router->post('{id}/start-review',                       [PostController::class, 'startReview']);
        $router->post('{id}/approve',                            [PostController::class, 'approve']);
        $router->post('{id}/reject',                             [PostController::class, 'reject']);
        $router->post('{id}/publish',                            [PostController::class, 'publish']);
        $router->post('{id}/schedule',                           [PostController::class, 'schedule']);
        $router->post('{id}/archive',                            [PostController::class, 'archive']);
        $router->post('{id}/duplicate',                          [PostController::class, 'duplicate']);
        $router->post('{id}/preview',                            [PostController::class, 'preview']);
        $router->get('{id}/revisions',                           [PostController::class, 'revisions']);
        $router->get('{id}/revisions/{revision_id}',             [PostController::class, 'revision']);
        $router->post('{id}/revisions/{revision_id}/restore',    [PostController::class, 'restoreRevision']);
    });

    // SEO metadata
    $router->group('seo', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('{entity_type}/{entity_id}', [SeoController::class, 'show']);
        $router->put('{entity_type}/{entity_id}', [SeoController::class, 'upsert']);
    });

    // Redirects
    $router->group('redirects', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('/',       [RedirectController::class, 'index']);
        $router->post('/',      [RedirectController::class, 'store']);
        $router->delete('{id}', [RedirectController::class, 'destroy']);
    });

    // Analytics — auth required
    $router->group('analytics', ['middleware' => [AuthMiddleware::class]], function ($router) {
        $router->get('summary',        [AnalyticsController::class, 'summary']);
        $router->get('posts/{id}/views', [AnalyticsController::class, 'postViews']);
    });

    // Public — no auth required
    $router->group('public', [], function ($router) {
        $router->get('posts',                 [PublicController::class, 'posts']);
        $router->get('posts/{slug}',          [PublicController::class, 'post']);
        $router->get('categories',            [PublicController::class, 'categories']);
        $router->get('categories/{slug}',     [PublicController::class, 'category']);
        $router->get('tags',                  [PublicController::class, 'tags']);
        $router->get('tags/{slug}',           [PublicController::class, 'tag']);
        $router->get('authors/{slug}',        [PublicController::class, 'author']);
        $router->get('sitemap.xml',           [PublicController::class, 'sitemapIndex']);
        $router->get('sitemap-posts.xml',     [PublicController::class, 'sitemapPosts']);
        $router->get('sitemap-categories.xml',[PublicController::class, 'sitemapCategories']);
        $router->get('sitemap-tags.xml',      [PublicController::class, 'sitemapTags']);
        $router->get('sitemap-authors.xml',   [PublicController::class, 'sitemapAuthors']);
        $router->get('rss.xml',               [PublicController::class, 'rss']);
        $router->get('robots.txt',            [PublicController::class, 'robots']);
    });

});
