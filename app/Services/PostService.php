<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Database;
use App\DTOs\CreatePostDTO;
use App\DTOs\UpdatePostDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\AuditRepository;
use App\Repositories\PostRepository;
use App\Repositories\TagRepository;

class PostService
{
    public function __construct(
        private readonly PostRepository  $postRepository,
        private readonly TagRepository   $tagRepository,
        private readonly AuditRepository $auditRepository,
    ) {}

    // ── Listing ───────────────────────────────────────────────────────────────

    public function all(int $websiteId, array $filters = []): array
    {
        $result = $this->postRepository->all($websiteId, $filters);

        $result['items'] = array_map(function (array $post) {
            $post['tags']       = $this->postRepository->tagsForPost($post['id']);
            $post['categories'] = $this->postRepository->categoriesForPost($post['id']);
            return $this->format($post);
        }, $result['items']);

        return $result;
    }

    public function trash(int $websiteId, array $filters = []): array
    {
        $result = $this->postRepository->trash($websiteId, $filters);

        $result['items'] = array_map(function (array $post) {
            return $this->format($post);
        }, $result['items']);

        return $result;
    }

    public function statusCounts(int $websiteId): array
    {
        return $this->postRepository->countsByStatus($websiteId);
    }

    // ── Single ────────────────────────────────────────────────────────────────

    public function findById(int $id, int $websiteId): array
    {
        $post = $this->postRepository->findById($id, $websiteId);

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $post['tags']          = $this->postRepository->tagsForPost($id);
        $post['categories']    = $this->postRepository->categoriesForPost($id);
        $post['revisions']     = $this->postRepository->revisionsForPost($id);
        $post['workflow_logs'] = $this->postRepository->workflowLogsForPost($id);

        return $this->format($post);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(CreatePostDTO $dto): array
    {
        if ($this->postRepository->findBySlug($dto->slug, $dto->websiteId) !== null) {
            throw new AppException('A post with this slug already exists.', 409);
        }

        Database::beginTransaction();
        try {
            $id = $this->postRepository->create($dto);

            if (!empty($dto->tags)) {
                $tagIds = $this->resolveTagIds($dto->tags, $dto->websiteId);
                $this->postRepository->syncTags($id, $tagIds);
            }

            if (!empty($dto->categoryIds)) {
                $this->postRepository->syncCategories($id, $dto->categoryIds, $dto->categoryId);
            } elseif ($dto->categoryId) {
                $this->postRepository->syncCategories($id, [$dto->categoryId], $dto->categoryId);
            }

            $this->postRepository->saveRevision($id, $dto->title, $dto->contentHtml, $dto->contentJson, $dto->authorId, 'Initial draft');
            $this->postRepository->logWorkflow($id, $dto->authorId, null, $dto->status);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        $this->auditRepository->log(
            userId: $dto->authorId,
            action: 'post.created',
            websiteId: $dto->websiteId,
            entityType: 'post',
            entityId: $id,
        );

        $this->bustCache($dto->websiteId);

        return $this->findById($id, $dto->websiteId);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(int $id, int $websiteId, UpdatePostDTO $dto): array
    {
        $post = $this->postRepository->findById($id, $websiteId);
        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        if ($dto->slug !== null && $dto->slug !== $post['slug']) {
            if ($this->postRepository->findBySlug($dto->slug, $websiteId) !== null) {
                throw new AppException('A post with this slug already exists.', 409);
            }
        }

        Database::beginTransaction();
        try {
            $this->postRepository->update($id, $dto);

            if ($dto->tags !== null) {
                $tagIds = $this->resolveTagIds($dto->tags, $websiteId);
                $this->postRepository->syncTags($id, $tagIds);
            }

            if ($dto->categoryIds !== null) {
                $primaryId = $dto->categoryId ?? ($dto->categoryIds[0] ?? null);
                $this->postRepository->syncCategories($id, $dto->categoryIds, $primaryId);
            } elseif ($dto->categoryId !== null) {
                $this->postRepository->syncCategories($id, [$dto->categoryId], $dto->categoryId);
            }

            if ($dto->contentHtml !== null || $dto->contentJson !== null) {
                $this->postRepository->saveRevision(
                    $id,
                    $dto->title ?? $post['title'],
                    $dto->contentHtml,
                    $dto->contentJson,
                    $dto->editorId,
                );
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        $this->auditRepository->log(
            userId: $dto->editorId,
            action: 'post.updated',
            websiteId: $websiteId,
            entityType: 'post',
            entityId: $id,
        );

        $this->bustCache($websiteId, $post['slug']);

        return $this->findById($id, $websiteId);
    }

    // ── Workflow ──────────────────────────────────────────────────────────────

    public function submitReview(int $id, int $websiteId, int $userId, ?string $notes = null): array
    {
        $post = $this->getPost($id, $websiteId);

        if (!in_array($post['status'], ['draft', 'rejected'], true)) {
            throw new AppException('Only drafts or rejected posts can be submitted for review.', 422);
        }

        $dto = new UpdatePostDTO(['status' => 'review_requested', 'review_notes' => $notes], $userId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $userId, $post['status'], 'review_requested', $notes);

        $this->auditRepository->log($userId, 'post.submitted_review', $websiteId, 'post', $id);

        return $this->findById($id, $websiteId);
    }

    public function startReview(int $id, int $websiteId, int $editorId): array
    {
        $post = $this->getPost($id, $websiteId);

        if ($post['status'] !== 'review_requested') {
            throw new AppException('Post must be in review_requested status.', 422);
        }

        $dto = new UpdatePostDTO(['status' => 'in_review'], $editorId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $editorId, 'review_requested', 'in_review');

        return $this->findById($id, $websiteId);
    }

    public function approve(int $id, int $websiteId, int $editorId, ?string $notes = null): array
    {
        $post = $this->getPost($id, $websiteId);

        if (!in_array($post['status'], ['in_review', 'review_requested'], true)) {
            throw new AppException('Post must be in review to approve.', 422);
        }

        $dto = new UpdatePostDTO(['status' => 'approved', 'editor_notes' => $notes], $editorId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $editorId, $post['status'], 'approved', $notes);

        $this->auditRepository->log($editorId, 'post.approved', $websiteId, 'post', $id);

        return $this->findById($id, $websiteId);
    }

    public function reject(int $id, int $websiteId, int $editorId, ?string $reason = null): array
    {
        $post = $this->getPost($id, $websiteId);

        if (!in_array($post['status'], ['in_review', 'review_requested'], true)) {
            throw new AppException('Post must be in review to reject.', 422);
        }

        $dto = new UpdatePostDTO(['status' => 'rejected', 'rejection_reason' => $reason], $editorId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $editorId, $post['status'], 'rejected', $reason);

        $this->auditRepository->log($editorId, 'post.rejected', $websiteId, 'post', $id);

        return $this->findById($id, $websiteId);
    }

    public function publish(int $id, int $websiteId, int $userId): array
    {
        $post = $this->getPost($id, $websiteId);

        if ($post['status'] === 'published') {
            throw new AppException('Post is already published.', 422);
        }

        $this->postRepository->publish($id, $userId);
        $this->postRepository->logWorkflow($id, $userId, $post['status'], 'published');

        $this->auditRepository->log($userId, 'post.published', $websiteId, 'post', $id);
        $this->bustCache($websiteId, $post['slug']);

        return $this->findById($id, $websiteId);
    }

    public function schedule(int $id, int $websiteId, string $scheduledAt, int $userId): array
    {
        $post = $this->getPost($id, $websiteId);

        $dto = new UpdatePostDTO(['status' => 'scheduled', 'scheduled_at' => $scheduledAt], $userId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $userId, $post['status'], 'scheduled', "Scheduled for {$scheduledAt}");

        return $this->findById($id, $websiteId);
    }

    public function archive(int $id, int $websiteId, int $userId): array
    {
        $post = $this->getPost($id, $websiteId);

        $dto = new UpdatePostDTO(['status' => 'archived'], $userId);
        $this->postRepository->update($id, $dto);
        $this->postRepository->logWorkflow($id, $userId, $post['status'], 'archived');

        $this->auditRepository->log($userId, 'post.archived', $websiteId, 'post', $id);
        $this->bustCache($websiteId, $post['slug']);

        return $this->findById($id, $websiteId);
    }

    // ── Delete / Trash ────────────────────────────────────────────────────────

    public function delete(int $id, int $websiteId, int $userId): void
    {
        $post = $this->getPost($id, $websiteId);

        $this->postRepository->softDelete($id, $userId);
        $this->postRepository->logWorkflow($id, $userId, $post['status'], 'deleted');

        $this->auditRepository->log($userId, 'post.deleted', $websiteId, 'post', $id);
        $this->bustCache($websiteId, $post['slug']);
    }

    public function restoreFromTrash(int $id, int $websiteId, int $userId): array
    {
        $post = $this->postRepository->findById($id, $websiteId, includeDeleted: true);
        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $this->postRepository->restore($id);
        $this->postRepository->logWorkflow($id, $userId, 'deleted', 'draft');

        return $this->findById($id, $websiteId);
    }

    public function forceDelete(int $id, int $websiteId): void
    {
        $post = $this->postRepository->findById($id, $websiteId, includeDeleted: true);
        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $this->postRepository->forceDelete($id);
        $this->bustCache($websiteId, $post['slug']);
    }

    // ── Revisions ─────────────────────────────────────────────────────────────

    public function revisions(int $id, int $websiteId): array
    {
        $this->getPost($id, $websiteId);
        return $this->postRepository->revisionsForPost($id);
    }

    public function revision(int $id, int $websiteId, int $revisionId): array
    {
        $this->getPost($id, $websiteId);
        $revision = $this->postRepository->revisionById($revisionId, $id);
        if ($revision === null) {
            throw new NotFoundException('Revision not found.');
        }
        return $revision;
    }

    public function restoreRevision(int $id, int $websiteId, int $revisionId, int $userId): array
    {
        $post     = $this->getPost($id, $websiteId);
        $revision = $this->postRepository->revisionById($revisionId, $id);
        if ($revision === null) {
            throw new NotFoundException('Revision not found.');
        }

        $dto = new UpdatePostDTO([
            'title'   => $revision['title'] ?? $post['title'],
            'content' => $revision['content'],
            'content_json' => $revision['content_json'],
        ], $userId);

        $this->postRepository->update($id, $dto);
        $this->postRepository->saveRevision($id, $post['title'], $revision['content'], $revision['content_json'], $userId, "Restored from revision #{$revisionId}");

        return $this->findById($id, $websiteId);
    }

    // ── Preview ───────────────────────────────────────────────────────────────

    public function createPreviewToken(int $id, int $websiteId, int $userId): string
    {
        $this->getPost($id, $websiteId);
        return $this->postRepository->createPreviewToken($id, $userId);
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicate(int $id, int $websiteId, int $userId): array
    {
        $post = $this->findById($id, $websiteId);

        $dto = new CreatePostDTO([
            'title'              => $post['title'] . ' (Copy)',
            'subtitle'           => $post['subtitle'],
            'slug'               => $post['slug'] . '-copy-' . time(),
            'excerpt'            => $post['excerpt'],
            'content'            => $post['content'],
            'content_json'       => $post['content_json'],
            'category_id'        => $post['category_id'],
            'featured_image'     => $post['featured_image'],
            'featured_image_alt' => $post['featured_image_alt'],
            'seo_title'          => $post['seo_title'],
            'seo_description'    => $post['seo_description'],
            'focus_keyword'      => $post['focus_keyword'],
            'status'             => 'draft',
            'visibility'         => $post['visibility'],
            'tags'               => array_column($post['tags'], 'id'),
            'category_ids'       => array_column($post['categories'], 'id'),
        ], $websiteId, $userId);

        return $this->create($dto);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getPost(int $id, int $websiteId): array
    {
        $post = $this->postRepository->findById($id, $websiteId);
        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }
        return $post;
    }

    private function format(array $post): array
    {
        // Cast booleans
        foreach (['is_featured', 'is_sticky', 'is_breaking_news', 'show_on_homepage', 'include_in_sitemap', 'include_in_rss'] as $f) {
            if (isset($post[$f])) {
                $post[$f] = (bool) $post[$f];
            }
        }

        // Decode JSON content_json if stored as string
        if (isset($post['content_json']) && is_string($post['content_json'])) {
            $decoded = json_decode($post['content_json'], true);
            if ($decoded !== null) {
                $post['content_json'] = $decoded;
            }
        }

        return $post;
    }

    private function resolveTagIds(array $tags, int $websiteId): array
    {
        if (empty($tags)) {
            return [];
        }

        if (is_int($tags[0] ?? null)) {
            return $tags;
        }

        return $this->tagRepository->findOrCreateByNames($tags, $websiteId);
    }

    private function bustCache(int $websiteId, ?string $slug = null): void
    {
        Cache::flushTag("posts:{$websiteId}");

        if ($slug !== null) {
            Cache::forget("public:post:{$websiteId}:{$slug}");
        }
    }
}
