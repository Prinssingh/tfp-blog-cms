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

    // ── Listing ──────────────────────────────────────────────────────────────

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT p.*,
                          u.name AS author_name, u.avatar AS author_avatar,
                          c.name AS category_name, c.slug AS category_slug
                   FROM posts p
                   JOIN users u ON u.id = p.author_id
                   LEFT JOIN categories c ON c.id = p.category_id
                   WHERE p.website_id = ? AND p.deleted_at IS NULL';
        $params = [$websiteId];

        if (!empty($filters['status'])) {
            $sql      .= ' AND p.status = ?';
            $params[] = $filters['status'];
        } else {
            $sql .= ' AND p.status != "deleted"';
        }

        if (!empty($filters['author_id'])) {
            $sql      .= ' AND p.author_id = ?';
            $params[] = (int) $filters['author_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql      .= ' AND (p.category_id = ? OR EXISTS (SELECT 1 FROM post_categories pc WHERE pc.post_id = p.id AND pc.category_id = ?))';
            $params[] = (int) $filters['category_id'];
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['tag_id'])) {
            $sql      .= ' AND EXISTS (SELECT 1 FROM post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = ?)';
            $params[] = (int) $filters['tag_id'];
        }

        if (!empty($filters['search'])) {
            $sql      .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.plain_text_content LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['date_from'])) {
            $sql      .= ' AND p.created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql      .= ' AND p.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $allowed = ['title', 'published_at', 'created_at', 'updated_at', 'views', 'word_count'];
        $sort    = in_array($filters['sort'] ?? '', $allowed, true) ? $filters['sort'] : 'created_at';
        $order   = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql    .= " ORDER BY p.{$sort} {$order}";

        $page   = max(1, (int) ($filters['page'] ?? 1));
        $limit  = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $countSql  = preg_replace('/SELECT p\.\*.*?FROM/s', 'SELECT COUNT(*) FROM', $sql);
        $countSql  = preg_replace('/ORDER BY.*$/', '', $countSql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql  .= " LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function trash(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT p.*,
                          u.name AS author_name, u.avatar AS author_avatar,
                          c.name AS category_name, c.slug AS category_slug
                   FROM posts p
                   JOIN users u ON u.id = p.author_id
                   LEFT JOIN categories c ON c.id = p.category_id
                   WHERE p.website_id = ? AND p.deleted_at IS NOT NULL';
        $params = [$websiteId];

        $page   = max(1, (int) ($filters['page'] ?? 1));
        $limit  = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $countSql  = str_replace('SELECT p.*,
                          u.name AS author_name, u.avatar AS author_avatar,
                          c.name AS category_name, c.slug AS category_slug', 'SELECT COUNT(*)', $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql  .= " ORDER BY p.deleted_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    // ── Single record ─────────────────────────────────────────────────────────

    public function findById(int $id, int $websiteId, bool $includeDeleted = false): ?array
    {
        $deletedFilter = $includeDeleted ? '' : 'AND p.deleted_at IS NULL';
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    u.name AS author_name, u.avatar AS author_avatar, u.slug AS author_slug,
                    c.name AS category_name, c.slug AS category_slug
             FROM posts p
             JOIN users u ON u.id = p.author_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? AND p.website_id = ? {$deletedFilter}
             LIMIT 1"
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

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(CreatePostDTO $dto): int
    {
        $wordCount  = $this->countWords($dto->contentHtml ?? '');
        $readingTime = max(1, (int) ceil($wordCount / 200));
        $plainText  = strip_tags($dto->contentHtml ?? '');

        $stmt = $this->db->prepare(
            'INSERT INTO posts (
                website_id, author_id, category_id, title, subtitle, slug, excerpt, summary,
                content, content_json, plain_text_content,
                reading_time, word_count, character_count,
                status, visibility, priority, password, scheduled_at, language,
                featured_image, featured_image_alt, featured_image_caption, featured_image_credit,
                seo_title, seo_description, focus_keyword, canonical_url, robots_directive,
                og_title, og_description, og_image,
                twitter_title, twitter_description, twitter_image,
                is_featured, is_sticky, show_on_homepage, include_in_sitemap, include_in_rss,
                internal_notes, created_at, updated_at
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, NOW(), NOW()
             )'
        );

        $stmt->execute([
            $dto->websiteId,   $dto->authorId,       $dto->categoryId,
            $dto->title,       $dto->subtitle,       $dto->slug,
            $dto->excerpt,     $dto->summary,
            $dto->contentHtml, $dto->contentJson,    $plainText,
            $readingTime,      $wordCount,            strlen($plainText),
            $dto->status,      $dto->visibility,     $dto->priority,
            $dto->password,    $dto->scheduledAt,    $dto->language,
            $dto->featuredImage,        $dto->featuredImageAlt,
            $dto->featuredImageCaption, $dto->featuredImageCredit,
            $dto->seoTitle,      $dto->seoDescription, $dto->focusKeyword,
            $dto->canonicalUrl,  $dto->robotsDirective,
            $dto->ogTitle,       $dto->ogDescription,  $dto->ogImage,
            $dto->twitterTitle,  $dto->twitterDescription, $dto->twitterImage,
            $dto->isFeatured ? 1 : 0, $dto->isSticky ? 1 : 0,
            $dto->showOnHomepage ? 1 : 0,
            $dto->includeInSitemap ? 1 : 0, $dto->includeInRss ? 1 : 0,
            $dto->internalNotes,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdatePostDTO $dto): void
    {
        $fields = ['updated_at = NOW()', 'updated_by = ?'];
        $values = [$dto->editorId];

        $map = [
            'category_id'            => $dto->categoryId,
            'title'                  => $dto->title,
            'subtitle'               => $dto->subtitle,
            'slug'                   => $dto->slug,
            'excerpt'                => $dto->excerpt,
            'summary'                => $dto->summary,
            'status'                 => $dto->status,
            'visibility'             => $dto->visibility,
            'priority'               => $dto->priority,
            'password'               => $dto->password,
            'scheduled_at'           => $dto->scheduledAt,
            'language'               => $dto->language,
            'featured_image'         => $dto->featuredImage,
            'featured_image_alt'     => $dto->featuredImageAlt,
            'featured_image_caption' => $dto->featuredImageCaption,
            'featured_image_credit'  => $dto->featuredImageCredit,
            'seo_title'              => $dto->seoTitle,
            'seo_description'        => $dto->seoDescription,
            'focus_keyword'          => $dto->focusKeyword,
            'canonical_url'          => $dto->canonicalUrl,
            'robots_directive'       => $dto->robotsDirective,
            'og_title'               => $dto->ogTitle,
            'og_description'         => $dto->ogDescription,
            'og_image'               => $dto->ogImage,
            'twitter_title'          => $dto->twitterTitle,
            'twitter_description'    => $dto->twitterDescription,
            'twitter_image'          => $dto->twitterImage,
            'review_notes'           => $dto->reviewNotes,
            'editor_notes'           => $dto->editorNotes,
            'internal_notes'         => $dto->internalNotes,
            'rejection_reason'       => $dto->rejectionReason,
        ];

        foreach ($map as $col => $val) {
            if ($val !== null) {
                $fields[] = "{$col} = ?";
                $values[] = $val;
            }
        }

        $boolMap = [
            'is_featured'      => $dto->isFeatured,
            'is_sticky'        => $dto->isSticky,
            'show_on_homepage' => $dto->showOnHomepage,
            'include_in_sitemap' => $dto->includeInSitemap,
            'include_in_rss'   => $dto->includeInRss,
        ];

        foreach ($boolMap as $col => $val) {
            if ($val !== null) {
                $fields[] = "{$col} = ?";
                $values[] = $val ? 1 : 0;
            }
        }

        if ($dto->contentHtml !== null) {
            $plainText   = strip_tags($dto->contentHtml);
            $wordCount   = $this->countWords($dto->contentHtml);
            $readingTime = max(1, (int) ceil($wordCount / 200));
            $fields[]    = 'content = ?';
            $values[]    = $dto->contentHtml;
            $fields[]    = 'plain_text_content = ?';
            $values[]    = $plainText;
            $fields[]    = 'word_count = ?';
            $values[]    = $wordCount;
            $fields[]    = 'character_count = ?';
            $values[]    = strlen($plainText);
            $fields[]    = 'reading_time = ?';
            $values[]    = $readingTime;
        }

        if ($dto->contentJson !== null) {
            $fields[] = 'content_json = ?';
            $values[] = $dto->contentJson;
        }

        $values[] = $id;
        $this->db->prepare(
            'UPDATE posts SET ' . implode(', ', $fields) . ' WHERE id = ?'
        )->execute($values);
    }

    public function publish(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE posts SET
               status = "published",
               published_at = COALESCE(published_at, NOW()),
               first_published_at = COALESCE(first_published_at, NOW()),
               published_by = ?,
               scheduled_at = NULL,
               updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$userId, $id]);
    }

    public function setStatus(int $id, string $status, int $userId, ?string $comment = null): void
    {
        $extraFields = '';
        $extraValues = [];

        if ($status === 'published') {
            $extraFields  = ', published_at = COALESCE(published_at, NOW()), first_published_at = COALESCE(first_published_at, NOW()), published_by = ?';
            $extraValues[] = $userId;
        } elseif ($status === 'archived') {
            $extraFields  = ', unpublished_at = NOW()';
        } elseif ($status === 'approved') {
            $extraFields  = ', approved_by = ?';
            $extraValues[] = $userId;
        } elseif ($status === 'in_review') {
            $extraFields  = ', assigned_editor_id = ?';
            $extraValues[] = $userId;
        }

        $values = array_merge([$status], $extraValues, [$userId, $id]);
        $this->db->prepare(
            "UPDATE posts SET status = ?, updated_by = ?, updated_at = NOW() {$extraFields} WHERE id = ?"
        )->execute($values);

        // Wait - fix ordering: status=? then the extra values then updated_by=? updated_at then id=?
        // Let me rebuild this properly:
        $params = [$status];
        foreach ($extraValues as $v) {
            $params[] = $v;
        }
        $params[] = $userId;
        $params[] = $id;

        // Re-run with correct params
        $this->db->prepare(
            "UPDATE posts SET status = ? {$extraFields}, updated_by = ?, updated_at = NOW() WHERE id = ?"
        )->execute($params);
    }

    public function softDelete(int $id, int $userId): void
    {
        $this->db->prepare(
            'UPDATE posts SET status = "deleted", deleted_at = NOW(), deleted_by = ? WHERE id = ?'
        )->execute([$userId, $id]);
    }

    public function restore(int $id): void
    {
        $this->db->prepare(
            'UPDATE posts SET status = "draft", deleted_at = NULL, deleted_by = NULL WHERE id = ?'
        )->execute([$id]);
    }

    public function forceDelete(int $id): void
    {
        $this->db->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    public function syncTags(int $postId, array $tagIds): void
    {
        $this->db->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);

        if (empty($tagIds)) {
            return;
        }

        $stmt = $this->db->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, (int) $tagId]);
        }
    }

    public function tagsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.name, t.slug FROM tags t
             JOIN post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = ?
             ORDER BY t.name'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function syncCategories(int $postId, array $categoryIds, ?int $primaryId = null): void
    {
        $this->db->prepare('DELETE FROM post_categories WHERE post_id = ?')->execute([$postId]);

        if (empty($categoryIds)) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO post_categories (post_id, category_id, is_primary) VALUES (?, ?, ?)'
        );
        foreach ($categoryIds as $catId) {
            $stmt->execute([$postId, (int) $catId, (int) $catId === (int) $primaryId ? 1 : 0]);
        }
    }

    public function categoriesForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.slug, pc.is_primary FROM categories c
             JOIN post_categories pc ON pc.category_id = c.id
             WHERE pc.post_id = ?
             ORDER BY pc.is_primary DESC, c.name'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    // ── Revisions ─────────────────────────────────────────────────────────────

    public function saveRevision(int $postId, ?string $title, ?string $contentHtml, ?string $contentJson, int $userId, string $label = ''): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_revisions (post_id, title, content, content_json, revision_label, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$postId, $title, $contentHtml, $contentJson, $label ?: null, $userId]);
    }

    public function revisionsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.id, pr.post_id, pr.title, pr.revision_label,
                    LEFT(pr.content, 200) AS content_preview,
                    pr.created_at,
                    u.id AS created_by_id, u.name AS created_by_name, u.avatar AS created_by_avatar
             FROM post_revisions pr
             JOIN users u ON u.id = pr.created_by
             WHERE pr.post_id = ?
             ORDER BY pr.created_at DESC
             LIMIT 50'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function revisionById(int $revisionId, int $postId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*, u.name AS created_by_name
             FROM post_revisions pr
             JOIN users u ON u.id = pr.created_by
             WHERE pr.id = ? AND pr.post_id = ?
             LIMIT 1'
        );
        $stmt->execute([$revisionId, $postId]);
        return $stmt->fetch() ?: null;
    }

    // ── Workflow Logs ─────────────────────────────────────────────────────────

    public function logWorkflow(int $postId, int $userId, ?string $fromStatus, string $toStatus, ?string $comment = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_workflow_logs (post_id, user_id, from_status, to_status, comment, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$postId, $userId, $fromStatus, $toStatus, $comment]);
    }

    public function workflowLogsForPost(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT wl.*, u.name AS actor_name, u.avatar AS actor_avatar
             FROM post_workflow_logs wl
             JOIN users u ON u.id = wl.user_id
             WHERE wl.post_id = ?
             ORDER BY wl.created_at DESC'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    // ── Preview Tokens ────────────────────────────────────────────────────────

    public function createPreviewToken(int $postId, int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt  = $this->db->prepare(
            'INSERT INTO preview_tokens (post_id, token, created_by, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())'
        );
        $stmt->execute([$postId, $token, $userId]);
        return $token;
    }

    public function findByPreviewToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.* FROM posts p
             JOIN preview_tokens pt ON pt.post_id = p.id
             WHERE pt.token = ? AND pt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    // ── Scheduled jobs ────────────────────────────────────────────────────────

    public function scheduledDue(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM posts WHERE status = "scheduled" AND scheduled_at <= NOW() AND deleted_at IS NULL'
        );
        return $stmt->fetchAll();
    }

    // ── Counts for dashboard ──────────────────────────────────────────────────

    public function countsByStatus(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS count FROM posts
             WHERE website_id = ? AND deleted_at IS NULL
             GROUP BY status'
        );
        $stmt->execute([$websiteId]);
        $rows = $stmt->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        // Count deleted
        $stmt2 = $this->db->prepare(
            'SELECT COUNT(*) FROM posts WHERE website_id = ? AND deleted_at IS NOT NULL'
        );
        $stmt2->execute([$websiteId]);
        $counts['deleted'] = (int) $stmt2->fetchColumn();

        return $counts;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function countWords(string $html): int
    {
        return str_word_count(strip_tags($html));
    }
}
