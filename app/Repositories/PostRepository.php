<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreatePostDTO;
use App\DTOs\UpdatePostDTO;
use PDO;

class PostRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT p.*,
                          u.name AS author_name, u.slug AS author_slug,
                          c.name AS category_name, c.slug AS category_slug
                   FROM posts p
                   JOIN users u ON u.id = p.author_id
                   JOIN categories c ON c.id = p.category_id
                   WHERE p.website_id = ? AND p.deleted_at IS NULL';
        $params = [$websiteId];

        if (!empty($filters['status'])) {
            $sql      .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $sql      .= ' AND p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['author_id'])) {
            $sql      .= ' AND p.author_id = ?';
            $params[] = (int) $filters['author_id'];
        }

        if (!empty($filters['search'])) {
            $sql      .= ' AND (p.title LIKE ? OR p.excerpt LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sort  = in_array($filters['sort'] ?? '', ['title', 'published_at', 'created_at'], true)
            ? $filters['sort'] : 'created_at';
        $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY p.{$sort} {$order}";

        $page  = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $countStmt = $this->db->prepare(str_replace('SELECT p.*,
                          u.name AS author_name, u.slug AS author_slug,
                          c.name AS category_name, c.slug AS category_slug', 'SELECT COUNT(*)', $sql));
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function findById(int $id, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    u.name AS author_name, u.slug AS author_slug,
                    c.name AS category_name, c.slug AS category_slug
             FROM posts p
             JOIN users u ON u.id = p.author_id
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? AND p.website_id = ? AND p.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM posts WHERE slug = ? AND website_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$slug, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function create(CreatePostDTO $dto): int
    {
        $readingTime = $this->calculateReadingTime($dto->content ?? '');

        $stmt = $this->db->prepare(
            'INSERT INTO posts
                (website_id, author_id, category_id, title, slug, excerpt, content,
                 featured_image, featured_image_alt, reading_time, status, visibility,
                 password, scheduled_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $dto->websiteId,
            $dto->authorId,
            $dto->categoryId,
            $dto->title,
            $dto->slug,
            $dto->excerpt,
            $dto->content,
            $dto->featuredImage,
            $dto->featuredImageAlt,
            $readingTime,
            $dto->status,
            $dto->visibility,
            $dto->password,
            $dto->scheduledAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdatePostDTO $dto): void
    {
        $fields = [];
        $values = [];

        if ($dto->title !== null) {
            $fields[] = 'title = ?';
            $values[] = $dto->title;
        }
        if ($dto->slug !== null) {
            $fields[] = 'slug = ?';
            $values[] = $dto->slug;
        }
        if ($dto->categoryId !== null) {
            $fields[] = 'category_id = ?';
            $values[] = $dto->categoryId;
        }
        if ($dto->excerpt !== null) {
            $fields[] = 'excerpt = ?';
            $values[] = $dto->excerpt;
        }
        if ($dto->content !== null) {
            $fields[] = 'content = ?';
            $values[] = $dto->content;
            $fields[] = 'reading_time = ?';
            $values[] = $this->calculateReadingTime($dto->content);
        }
        if ($dto->featuredImage !== null) {
            $fields[] = 'featured_image = ?';
            $values[] = $dto->featuredImage;
        }
        if ($dto->featuredImageAlt !== null) {
            $fields[] = 'featured_image_alt = ?';
            $values[] = $dto->featuredImageAlt;
        }
        if ($dto->status !== null) {
            $fields[] = 'status = ?';
            $values[] = $dto->status;
        }
        if ($dto->visibility !== null) {
            $fields[] = 'visibility = ?';
            $values[] = $dto->visibility;
        }
        if ($dto->password !== null) {
            $fields[] = 'password = ?';
            $values[] = $dto->password;
        }
        if ($dto->scheduledAt !== null) {
            $fields[] = 'scheduled_at = ?';
            $values[] = $dto->scheduledAt;
        }
        if ($dto->editorId !== null) {
            $fields[] = 'editor_id = ?';
            $values[] = $dto->editorId;
        }

        if (empty($fields)) {
            return;
        }

        $fields[]  = 'updated_at = NOW()';
        $values[]  = $id;

        $this->db->prepare(
            'UPDATE posts SET ' . implode(', ', $fields) . ' WHERE id = ?'
        )->execute($values);
    }

    public function publish(int $id, int $editorId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE posts SET status = "published", published_at = NOW(), editor_id = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$editorId, $id]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE posts SET deleted_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function syncTags(int $postId, array $tagIds): void
    {
        $this->db->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);

        if (empty($tagIds)) {
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, (int) $tagId]);
        }
    }

    public function tagsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.* FROM tags t JOIN post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = ?'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function saveRevision(int $postId, string $content, int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_revisions (post_id, content, created_by, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$postId, $content, $userId]);
    }

    public function revisionsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*, u.name AS created_by_name
             FROM post_revisions pr
             JOIN users u ON u.id = pr.created_by
             WHERE pr.post_id = ?
             ORDER BY pr.created_at DESC'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function scheduledDue(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM posts WHERE status = "scheduled" AND scheduled_at <= NOW() AND deleted_at IS NULL'
        );
        return $stmt->fetchAll();
    }

    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }
}
