<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class PublicRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // ── Posts ────────────────────────────────────────────────────────────────

    public function posts(int $websiteId, array $filters = []): array
    {
        $page      = max(1, (int) ($filters['page'] ?? 1));
        $limit     = min(100, max(1, (int) ($filters['per_page'] ?? 10)));
        $offset    = ($page - 1) * $limit;

        $where  = ['p.website_id = ?', 'p.status = "published"', 'p.deleted_at IS NULL'];
        $params = [$websiteId];

        if (!empty($filters['category'])) {
            $where[]  = 'c.slug = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['tag'])) {
            $where[]  = 'EXISTS (SELECT 1 FROM post_tags pt JOIN tags t ON t.id = pt.tag_id WHERE pt.post_id = p.id AND t.slug = ?)';
            $params[] = $filters['tag'];
        }

        if (!empty($filters['author'])) {
            $where[]  = 'u.slug = ?';
            $params[] = $filters['author'];
        }

        if (!empty($filters['search'])) {
            $where[]  = '(p.title LIKE ? OR p.excerpt LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $whereStr = implode(' AND ', $where);
        $order    = match($filters['sort'] ?? 'latest') {
            'popular' => 'p.views DESC',
            'oldest'  => 'p.published_at ASC',
            default   => 'p.published_at DESC',
        };

        $cols = 'p.id, p.slug, p.title, p.excerpt, p.featured_image, p.featured_image_alt,
                 p.published_at, p.reading_time, p.views,
                 c.name AS category_name, c.slug AS category_slug,
                 u.name AS author_name, u.slug AS author_slug, u.avatar AS author_avatar';

        $countParams = $params;
        $countStmt   = $this->db->prepare(
            "SELECT COUNT(*) FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.author_id
             WHERE {$whereStr}"
        );
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare(
            "SELECT {$cols} FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.author_id
             WHERE {$whereStr}
             ORDER BY {$order}
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['tags'] = $this->tagsForPost((int) $post['id']);
        }

        return compact('posts', 'total', 'page', 'limit');
    }

    public function postBySlug(int $websiteId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    u.name AS author_name, u.slug AS author_slug, u.avatar AS author_avatar, u.bio AS author_bio
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.author_id
             WHERE p.website_id = ? AND p.slug = ? AND p.status = "published" AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$websiteId, $slug]);
        $post = $stmt->fetch() ?: null;

        if ($post) {
            $post['tags'] = $this->tagsForPost((int) $post['id']);
            $post['seo']  = $this->seoFor($websiteId, 'post', (int) $post['id']);
        }

        return $post;
    }

    public function related(int $websiteId, int $postId, int $categoryId, int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.slug, p.title, p.excerpt, p.featured_image, p.published_at, p.reading_time,
                    u.name AS author_name, u.slug AS author_slug
             FROM posts p
             LEFT JOIN users u ON u.id = p.author_id
             WHERE p.website_id = ? AND p.category_id = ? AND p.id != ?
               AND p.status = "published" AND p.deleted_at IS NULL
             ORDER BY p.published_at DESC
             LIMIT ?'
        );
        $stmt->execute([$websiteId, $categoryId, $postId, $limit]);
        return $stmt->fetchAll();
    }

    public function incrementViews(int $postId): void
    {
        $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$postId]);
    }

    // ── Categories ───────────────────────────────────────────────────────────

    public function categories(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, COUNT(p.id) AS post_count
             FROM categories c
             LEFT JOIN posts p ON p.category_id = c.id AND p.status = "published" AND p.deleted_at IS NULL
             WHERE c.website_id = ?
             GROUP BY c.id
             ORDER BY c.name ASC'
        );
        $stmt->execute([$websiteId]);
        return $stmt->fetchAll();
    }

    public function categoryBySlug(int $websiteId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, COUNT(p.id) AS post_count
             FROM categories c
             LEFT JOIN posts p ON p.category_id = c.id AND p.status = "published" AND p.deleted_at IS NULL
             WHERE c.website_id = ? AND c.slug = ?
             GROUP BY c.id
             LIMIT 1'
        );
        $stmt->execute([$websiteId, $slug]);
        $cat = $stmt->fetch() ?: null;
        if ($cat) {
            $cat['seo'] = $this->seoFor($websiteId, 'category', (int) $cat['id']);
        }
        return $cat;
    }

    // ── Tags ─────────────────────────────────────────────────────────────────

    public function tags(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, COUNT(pt.post_id) AS post_count
             FROM tags t
             LEFT JOIN post_tags pt ON pt.tag_id = t.id
             LEFT JOIN posts p ON p.id = pt.post_id AND p.status = "published" AND p.deleted_at IS NULL
             WHERE t.website_id = ?
             GROUP BY t.id
             ORDER BY post_count DESC'
        );
        $stmt->execute([$websiteId]);
        return $stmt->fetchAll();
    }

    public function tagBySlug(int $websiteId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT t.* FROM tags t WHERE t.website_id = ? AND t.slug = ? LIMIT 1'
        );
        $stmt->execute([$websiteId, $slug]);
        return $stmt->fetch() ?: null;
    }

    // ── Authors ──────────────────────────────────────────────────────────────

    public function authorBySlug(int $websiteId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.slug, u.bio, u.avatar,
                    COUNT(p.id) AS post_count
             FROM users u
             LEFT JOIN posts p ON p.author_id = u.id AND p.website_id = ? AND p.status = "published" AND p.deleted_at IS NULL
             WHERE u.slug = ?
             GROUP BY u.id
             LIMIT 1'
        );
        $stmt->execute([$websiteId, $slug]);
        return $stmt->fetch() ?: null;
    }

    // ── Website ──────────────────────────────────────────────────────────────

    public function websiteByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM websites WHERE domain = ? AND status = "active" LIMIT 1'
        );
        $stmt->execute([$domain]);
        return $stmt->fetch() ?: null;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function tagsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.name, t.slug FROM tags t
             JOIN post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = ?'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    private function seoFor(int $websiteId, string $entityType, int $entityId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM seo_metadata WHERE website_id = ? AND entity_type = ? AND entity_id = ? LIMIT 1'
        );
        $stmt->execute([$websiteId, $entityType, $entityId]);
        return $stmt->fetch() ?: null;
    }
}
