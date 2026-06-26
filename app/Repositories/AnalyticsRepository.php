<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AnalyticsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // ── View tracking ─────────────────────────────────────────────────────────

    public function recordView(int $postId, int $websiteId, string $ipHash): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT IGNORE INTO post_views (post_id, website_id, ip_hash, viewed_at)
                 VALUES (?, ?, ?, CURDATE())'
            );
            $stmt->execute([$postId, $websiteId, $ipHash]);

            if ($stmt->rowCount() > 0) {
                $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$postId]);
                return true;
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Search tracking ───────────────────────────────────────────────────────

    public function recordSearch(int $websiteId, string $term, int $results): void
    {
        $this->db->prepare(
            'INSERT INTO search_terms (website_id, term, results) VALUES (?, ?, ?)'
        )->execute([$websiteId, mb_strtolower(trim($term)), $results]);
    }

    // ── Dashboard summary ─────────────────────────────────────────────────────

    public function summary(int $websiteId, string $period = '30'): array
    {
        $days = (int) $period;

        // Total posts
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM posts WHERE website_id = ? AND status = "published" AND deleted_at IS NULL'
        );
        $stmt->execute([$websiteId]);
        $totalPosts = (int) $stmt->fetchColumn();

        // Total views in period
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM post_views
             WHERE website_id = ? AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)'
        );
        $stmt->execute([$websiteId, $days]);
        $totalViews = (int) $stmt->fetchColumn();

        // Views per day
        $stmt = $this->db->prepare(
            'SELECT viewed_at AS date, COUNT(*) AS views
             FROM post_views
             WHERE website_id = ? AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY viewed_at
             ORDER BY viewed_at ASC'
        );
        $stmt->execute([$websiteId, $days]);
        $viewsPerDay = $stmt->fetchAll();

        // Popular posts in period
        $stmt = $this->db->prepare(
            'SELECT p.id, p.title, p.slug, p.views,
                    COUNT(pv.id) AS period_views
             FROM posts p
             LEFT JOIN post_views pv ON pv.post_id = p.id AND pv.viewed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             WHERE p.website_id = ? AND p.status = "published" AND p.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY period_views DESC
             LIMIT 10'
        );
        $stmt->execute([$days, $websiteId]);
        $popularPosts = $stmt->fetchAll();

        // Top search terms
        $stmt = $this->db->prepare(
            'SELECT term, COUNT(*) AS count, AVG(results) AS avg_results
             FROM search_terms
             WHERE website_id = ? AND searched_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY term
             ORDER BY count DESC
             LIMIT 20'
        );
        $stmt->execute([$websiteId, $days]);
        $topSearchTerms = $stmt->fetchAll();

        // New posts in period
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM posts
             WHERE website_id = ? AND status = "published" AND deleted_at IS NULL
               AND published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$websiteId, $days]);
        $newPosts = (int) $stmt->fetchColumn();

        // Posts by status
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS count FROM posts
             WHERE website_id = ? AND deleted_at IS NULL
             GROUP BY status'
        );
        $stmt->execute([$websiteId]);
        $postsByStatus = $stmt->fetchAll();

        return [
            'period_days'     => $days,
            'total_posts'     => $totalPosts,
            'new_posts'       => $newPosts,
            'total_views'     => $totalViews,
            'views_per_day'   => $viewsPerDay,
            'popular_posts'   => $popularPosts,
            'top_searches'    => $topSearchTerms,
            'posts_by_status' => $postsByStatus,
        ];
    }

    public function postViews(int $postId, int $websiteId, int $days = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT viewed_at AS date, COUNT(*) AS views
             FROM post_views
             WHERE post_id = ? AND website_id = ? AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY viewed_at
             ORDER BY viewed_at ASC'
        );
        $stmt->execute([$postId, $websiteId, $days]);
        return $stmt->fetchAll();
    }
}
