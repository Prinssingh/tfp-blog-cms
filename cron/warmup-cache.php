<?php

/**
 * Cron: Pre-warm public cache after midnight when traffic is low.
 * Run daily at 3am:
 *   0 3 * * * php /home/u512841431/domains/blog-cms.tfptechnologies.in/warmup-cache.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Cache;
use App\Core\Database;
use App\Repositories\PublicRepository;
use App\Services\SitemapService;

$db          = Database::connection();
$publicRepo  = new PublicRepository();
$sitemapSvc  = new SitemapService();

// Get all active websites
$stmt = $db->prepare('SELECT id, domain FROM websites WHERE status = "active"');
$stmt->execute();
$websites = $stmt->fetchAll();

$baseUrl = $_ENV['APP_URL'] ?? '';

foreach ($websites as $website) {
    $websiteId = (int) $website['id'];

    // Warm posts list
    $result = $publicRepo->posts($websiteId, ['per_page' => 10, 'sort' => 'latest']);
    Cache::set("public:posts:{$websiteId}:" . md5(serialize(['page' => 1, 'per_page' => 10, 'sort' => 'latest'])), $result, 300, ["posts:{$websiteId}"]);

    // Warm categories
    $categories = $publicRepo->categories($websiteId);
    Cache::set("public:categories:{$websiteId}", $categories, 900, ["categories:{$websiteId}"]);

    // Warm tags
    $tags = $publicRepo->tags($websiteId);
    Cache::set("public:tags:{$websiteId}", $tags, 900, ["tags:{$websiteId}"]);

    // Warm sitemaps
    Cache::set("sitemap:posts:{$websiteId}", $sitemapSvc->generatePosts($websiteId, $baseUrl), 3600, ["posts:{$websiteId}"]);
    Cache::set("sitemap:categories:{$websiteId}", $sitemapSvc->generateCategories($websiteId, $baseUrl), 3600, ["categories:{$websiteId}"]);
    Cache::set("sitemap:tags:{$websiteId}", $sitemapSvc->generateTags($websiteId, $baseUrl), 3600, ["tags:{$websiteId}"]);

    echo "[" . date('Y-m-d H:i:s') . "] Warmed cache for website ID {$websiteId} ({$website['domain']})\n";
}

exit(0);
