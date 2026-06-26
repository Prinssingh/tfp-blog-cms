<?php

/**
 * Cron: Publish scheduled posts whose scheduled_at has passed.
 * Run every 5 minutes:
 *   *\/5 * * * * php /home/u512841431/domains/blog-cms.tfptechnologies.in/publish-scheduled.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Cache;
use App\Core\Database;

$db = Database::connection();

$stmt = $db->prepare(
    'SELECT id, website_id, slug FROM posts
     WHERE status = "scheduled"
       AND scheduled_at <= NOW()
       AND deleted_at IS NULL'
);
$stmt->execute();
$posts = $stmt->fetchAll();

if (empty($posts)) {
    exit(0);
}

$update = $db->prepare(
    'UPDATE posts SET status = "published", published_at = NOW(), updated_at = NOW()
     WHERE id = ?'
);

$audit = $db->prepare(
    'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, website_id, created_at)
     VALUES (NULL, "post.auto_published", "post", ?, ?, NOW())'
);

foreach ($posts as $post) {
    $update->execute([$post['id']]);
    $audit->execute([$post['id'], $post['website_id']]);

    // Bust public cache for this website
    Cache::flushTag("posts:{$post['website_id']}");
    Cache::forget("public:post:{$post['website_id']}:{$post['slug']}");

    echo "[" . date('Y-m-d H:i:s') . "] Published post ID {$post['id']}: {$post['slug']}\n";
}

exit(0);
