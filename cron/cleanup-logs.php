<?php

/**
 * Cron: Delete old audit logs and search terms to keep the database lean.
 * Run daily at 2am:
 *   0 2 * * * php /home/u512841431/domains/blog-cms.tfptechnologies.in/cleanup-logs.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Database;

$db = Database::connection();

// Keep audit logs for 90 days
$stmt = $db->prepare('DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
$stmt->execute();
$deletedAudit = $stmt->rowCount();

// Keep search terms for 60 days
$stmt = $db->prepare('DELETE FROM search_terms WHERE searched_at < DATE_SUB(NOW(), INTERVAL 60 DAY)');
$stmt->execute();
$deletedSearches = $stmt->rowCount();

// Keep post_views for 365 days
$stmt = $db->prepare('DELETE FROM post_views WHERE viewed_at < DATE_SUB(CURDATE(), INTERVAL 365 DAY)');
$stmt->execute();
$deletedViews = $stmt->rowCount();

// Clean expired cache files
$cacheDir = BASE_PATH . '/storage/cache/data';
$cleaned  = 0;

if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*/*.cache') as $file) {
        $raw = @file_get_contents($file);
        if ($raw === false) {
            continue;
        }
        $data = @unserialize($raw);
        if (isset($data['expires']) && $data['expires'] !== 0 && $data['expires'] < time()) {
            @unlink($file);
            $cleaned++;
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete.\n";
echo "  Audit logs deleted: {$deletedAudit}\n";
echo "  Search terms deleted: {$deletedSearches}\n";
echo "  Post views deleted: {$deletedViews}\n";
echo "  Expired cache files removed: {$cleaned}\n";

exit(0);
