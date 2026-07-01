<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\UpdateCategoryDTO;
use PDO;

class CategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // ── Query ─────────────────────────────────────────────────────────────────

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT c.*, p.name AS parent_name
                   FROM categories c
                   LEFT JOIN categories p ON p.id = c.parent_id
                   WHERE c.website_id = ? AND c.deleted_at IS NULL';
        $params = [$websiteId];

        if (isset($filters['parent_id'])) {
            $sql      .= ' AND c.parent_id = ?';
            $params[] = (int) $filters['parent_id'];
        }
        if (isset($filters['status'])) {
            $sql      .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql      .= ' AND c.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY c.depth ASC, c.sort_order ASC, c.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function trash(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             WHERE c.website_id = ? AND c.deleted_at IS NOT NULL
             ORDER BY c.deleted_at DESC'
        );
        $stmt->execute([$websiteId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $websiteId, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT c.*, p.name AS parent_name
                FROM categories c
                LEFT JOIN categories p ON p.id = c.parent_id
                WHERE c.id = ? AND c.website_id = ?';
        if (!$includeDeleted) {
            $sql .= ' AND c.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categories WHERE slug = ? AND website_id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$slug, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(CreateCategoryDTO $dto): int
    {
        $depth = 0;
        $path  = $dto->slug;

        if ($dto->parentId !== null) {
            $parent = $this->db->prepare('SELECT depth, path FROM categories WHERE id = ? LIMIT 1');
            $parent->execute([$dto->parentId]);
            $row   = $parent->fetch();
            $depth = ($row['depth'] ?? 0) + 1;
            $path  = ($row['path'] ?? '') . '/' . $dto->slug;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO categories (
                website_id, parent_id, created_by,
                name, slug, description, short_description,
                depth, sort_order, path,
                image_url, image_alt, cover_image_url, icon,
                show_in_menu, show_in_homepage, show_in_footer, show_in_sidebar,
                is_featured, is_hidden, status,
                seo_title, seo_description, focus_keyword,
                canonical_url, robots_directive,
                og_title, og_description, og_image,
                twitter_title, twitter_description, twitter_image,
                include_in_sitemap, sitemap_priority, change_frequency,
                created_at, updated_at
             ) VALUES (
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                NOW(), NOW()
             )'
        );

        $stmt->execute([
            $dto->websiteId, $dto->parentId, $dto->createdBy,
            $dto->name, $dto->slug, $dto->description, $dto->shortDescription,
            $depth, $dto->sortOrder, $path,
            $dto->imageUrl, $dto->imageAlt, $dto->coverImageUrl, $dto->icon,
            (int) $dto->showInMenu, (int) $dto->showInHomepage,
            (int) $dto->showInFooter, (int) $dto->showInSidebar,
            (int) $dto->isFeatured, (int) $dto->isHidden, $dto->status,
            $dto->seoTitle, $dto->seoDescription, $dto->focusKeyword,
            $dto->canonicalUrl, $dto->robotsDirective,
            $dto->ogTitle, $dto->ogDescription, $dto->ogImage,
            $dto->twitterTitle, $dto->twitterDescription, $dto->twitterImage,
            (int) $dto->includeInSitemap, $dto->sitemapPriority, $dto->changeFrequency,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateCategoryDTO $dto): void
    {
        $fields = ['updated_at = NOW()', 'updated_by = ?'];
        $values = [$dto->updatedBy];

        $map = [
            'name'               => $dto->name,
            'slug'               => $dto->slug,
            'parent_id'          => $dto->parentId,
            'description'        => $dto->description,
            'short_description'  => $dto->shortDescription,
            'image_url'          => $dto->imageUrl,
            'image_alt'          => $dto->imageAlt,
            'cover_image_url'    => $dto->coverImageUrl,
            'icon'               => $dto->icon,
            'sort_order'         => $dto->sortOrder,
            'status'             => $dto->status,
            'show_in_menu'       => $dto->showInMenu !== null ? (int) $dto->showInMenu : null,
            'show_in_homepage'   => $dto->showInHomepage !== null ? (int) $dto->showInHomepage : null,
            'show_in_footer'     => $dto->showInFooter !== null ? (int) $dto->showInFooter : null,
            'show_in_sidebar'    => $dto->showInSidebar !== null ? (int) $dto->showInSidebar : null,
            'is_featured'        => $dto->isFeatured !== null ? (int) $dto->isFeatured : null,
            'is_hidden'          => $dto->isHidden !== null ? (int) $dto->isHidden : null,
            'seo_title'          => $dto->seoTitle,
            'seo_description'    => $dto->seoDescription,
            'focus_keyword'      => $dto->focusKeyword,
            'canonical_url'      => $dto->canonicalUrl,
            'robots_directive'   => $dto->robotsDirective,
            'og_title'           => $dto->ogTitle,
            'og_description'     => $dto->ogDescription,
            'og_image'           => $dto->ogImage,
            'twitter_title'      => $dto->twitterTitle,
            'twitter_description'=> $dto->twitterDescription,
            'twitter_image'      => $dto->twitterImage,
            'include_in_sitemap' => $dto->includeInSitemap !== null ? (int) $dto->includeInSitemap : null,
            'sitemap_priority'   => $dto->sitemapPriority,
            'change_frequency'   => $dto->changeFrequency,
        ];

        foreach ($map as $col => $val) {
            if ($val !== null) {
                $fields[] = "`$col` = ?";
                $values[] = $val;
            }
        }

        $values[] = $id;

        $this->db->prepare(
            'UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?'
        )->execute($values);

        // Re-compute path if slug or parent changed
        if ($dto->slug !== null || $dto->parentId !== null) {
            $row = $this->db->prepare('SELECT slug, parent_id FROM categories WHERE id = ?');
            $row->execute([$id]);
            $cat    = $row->fetch();
            $depth  = 0;
            $path   = $cat['slug'];

            if ($cat['parent_id']) {
                $p = $this->db->prepare('SELECT depth, path FROM categories WHERE id = ?');
                $p->execute([$cat['parent_id']]);
                $parent = $p->fetch();
                $depth  = ($parent['depth'] ?? 0) + 1;
                $path   = ($parent['path'] ?? '') . '/' . $cat['slug'];
            }

            $this->db->prepare('UPDATE categories SET depth = ?, path = ? WHERE id = ?')
                     ->execute([$depth, $path, $id]);
        }
    }

    public function softDelete(int $id, int $userId): void
    {
        $this->db->prepare(
            'UPDATE categories SET deleted_at = NOW(), deleted_by = ?, status = "archived", updated_at = NOW() WHERE id = ?'
        )->execute([$userId, $id]);
    }

    public function restore(int $id): void
    {
        $this->db->prepare(
            'UPDATE categories SET deleted_at = NULL, deleted_by = NULL, status = "active", updated_at = NOW() WHERE id = ?'
        )->execute([$id]);
    }

    public function forceDelete(int $id): void
    {
        // Reassign child categories to grandparent before hard deleting
        $stmt = $this->db->prepare('SELECT parent_id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $grandParentId = $row ? $row['parent_id'] : null;

        $this->db->prepare('UPDATE categories SET parent_id = ? WHERE parent_id = ?')
                 ->execute([$grandParentId, $id]);

        $this->db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    public function merge(int $sourceId, int $targetId, int $websiteId): void
    {
        // Move post_categories relationships
        $this->db->prepare(
            'UPDATE IGNORE post_categories SET category_id = ? WHERE category_id = ?'
        )->execute([$targetId, $sourceId]);

        // Move the legacy category_id on posts
        $this->db->prepare(
            'UPDATE posts SET category_id = ? WHERE category_id = ? AND website_id = ?'
        )->execute([$targetId, $sourceId, $websiteId]);

        // Update posts_count on target
        $this->db->prepare(
            'UPDATE categories SET posts_count = (
                SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL
             ) WHERE id = ?'
        )->execute([$targetId, $targetId]);

        // Hard delete source
        $this->db->prepare('DELETE FROM categories WHERE id = ? AND website_id = ?')
                 ->execute([$sourceId, $websiteId]);
    }

    public function incrementPostCount(int $id): void
    {
        $this->db->prepare('UPDATE categories SET posts_count = posts_count + 1 WHERE id = ?')->execute([$id]);
    }

    public function decrementPostCount(int $id): void
    {
        $this->db->prepare('UPDATE categories SET posts_count = GREATEST(0, posts_count - 1) WHERE id = ?')->execute([$id]);
    }
}
