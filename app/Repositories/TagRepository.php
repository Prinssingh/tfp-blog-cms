<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateTagDTO;
use PDO;

class TagRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // ── Query ─────────────────────────────────────────────────────────────────

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT * FROM tags WHERE website_id = ? AND deleted_at IS NULL';
        $params = [$websiteId];

        if (!empty($filters['search'])) {
            $sql      .= ' AND name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $sql      .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY posts_count DESC, name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function trash(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tags WHERE website_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC'
        );
        $stmt->execute([$websiteId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $websiteId, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT * FROM tags WHERE id = ? AND website_id = ?';
        if (!$includeDeleted) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tags WHERE slug = ? AND website_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$slug, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findOrCreateByNames(array $names, int $websiteId, int $userId = 0): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }
            $slug     = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');
            $existing = $this->findBySlug($slug, $websiteId);
            if ($existing) {
                $ids[] = $existing['id'];
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO tags (website_id, name, slug, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$websiteId, $name, $slug, $userId]);
                $ids[] = (int) $this->db->lastInsertId();
            }
        }
        return $ids;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(CreateTagDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tags (
                website_id, created_by,
                name, slug, description, color, icon, status,
                seo_title, seo_description, focus_keyword,
                canonical_url, robots_directive,
                created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $dto->websiteId, $dto->createdBy,
            $dto->name, $dto->slug, $dto->description,
            $dto->color, $dto->icon, $dto->status,
            $dto->seoTitle, $dto->seoDescription, $dto->focusKeyword,
            $dto->canonicalUrl, $dto->robotsDirective,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateTagDTO $dto): void
    {
        $fields = ['updated_at = NOW()', 'updated_by = ?'];
        $values = [$dto->updatedBy];

        $map = [
            'name'             => $dto->name,
            'slug'             => $dto->slug,
            'description'      => $dto->description,
            'color'            => $dto->color,
            'icon'             => $dto->icon,
            'status'           => $dto->status,
            'seo_title'        => $dto->seoTitle,
            'seo_description'  => $dto->seoDescription,
            'focus_keyword'    => $dto->focusKeyword,
            'canonical_url'    => $dto->canonicalUrl,
            'robots_directive' => $dto->robotsDirective,
        ];

        foreach ($map as $col => $val) {
            if ($val !== null) {
                $fields[] = "`$col` = ?";
                $values[] = $val;
            }
        }

        $values[] = $id;
        $this->db->prepare(
            'UPDATE tags SET ' . implode(', ', $fields) . ' WHERE id = ?'
        )->execute($values);
    }

    public function softDelete(int $id, int $userId): void
    {
        $this->db->prepare(
            'UPDATE tags SET deleted_at = NOW(), deleted_by = ?, status = "archived", updated_at = NOW() WHERE id = ?'
        )->execute([$userId, $id]);
    }

    public function restore(int $id): void
    {
        $this->db->prepare(
            'UPDATE tags SET deleted_at = NULL, deleted_by = NULL, status = "active", updated_at = NOW() WHERE id = ?'
        )->execute([$id]);
    }

    public function forceDelete(int $id): void
    {
        $this->db->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
    }

    public function merge(int $sourceId, int $targetId, int $websiteId): void
    {
        $this->db->prepare(
            'UPDATE IGNORE post_tags SET tag_id = ? WHERE tag_id = ?'
        )->execute([$targetId, $sourceId]);

        $this->db->prepare(
            'UPDATE tags SET posts_count = (SELECT COUNT(*) FROM post_tags WHERE tag_id = ?) WHERE id = ?'
        )->execute([$targetId, $targetId]);

        $this->db->prepare('DELETE FROM tags WHERE id = ? AND website_id = ?')
                 ->execute([$sourceId, $websiteId]);
    }
}
