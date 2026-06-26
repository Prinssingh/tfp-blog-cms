<?php

declare(strict_types=1);

namespace App\Services;

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

    public function all(int $websiteId, array $filters = []): array
    {
        $result = $this->postRepository->all($websiteId, $filters);

        $result['items'] = array_map(function (array $post) {
            $post['tags'] = $this->postRepository->tagsForPost($post['id']);
            return $post;
        }, $result['items']);

        return $result;
    }

    public function findById(int $id, int $websiteId): array
    {
        $post = $this->postRepository->findById($id, $websiteId);

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $post['tags']      = $this->postRepository->tagsForPost($id);
        $post['revisions'] = $this->postRepository->revisionsForPost($id);

        return $post;
    }

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

            if ($dto->content) {
                $this->postRepository->saveRevision($id, $dto->content, $dto->authorId);
            }

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

        return $this->findById($id, $dto->websiteId);
    }

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

            if ($dto->content !== null) {
                $this->postRepository->saveRevision($id, $dto->content, $dto->editorId);
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

        return $this->findById($id, $websiteId);
    }

    public function publish(int $id, int $websiteId, int $editorId): array
    {
        $post = $this->postRepository->findById($id, $websiteId);

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        if ($post['status'] === 'published') {
            throw new AppException('Post is already published.', 422);
        }

        $this->postRepository->publish($id, $editorId);

        $this->auditRepository->log(
            userId: $editorId,
            action: 'post.published',
            websiteId: $websiteId,
            entityType: 'post',
            entityId: $id,
        );

        return $this->findById($id, $websiteId);
    }

    public function schedule(int $id, int $websiteId, string $scheduledAt, int $editorId): array
    {
        $post = $this->postRepository->findById($id, $websiteId);

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $dto = new UpdatePostDTO(['status' => 'scheduled', 'scheduled_at' => $scheduledAt], $editorId);
        $this->postRepository->update($id, $dto);

        return $this->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId, int $userId): void
    {
        $post = $this->postRepository->findById($id, $websiteId);

        if ($post === null) {
            throw new NotFoundException('Post not found.');
        }

        $this->postRepository->softDelete($id);

        $this->auditRepository->log(
            userId: $userId,
            action: 'post.deleted',
            websiteId: $websiteId,
            entityType: 'post',
            entityId: $id,
        );
    }

    public function duplicate(int $id, int $websiteId, int $userId): array
    {
        $post = $this->findById($id, $websiteId);

        $dto = new CreatePostDTO([
            'title'              => $post['title'] . ' (Copy)',
            'slug'               => $post['slug'] . '-copy-' . time(),
            'excerpt'            => $post['excerpt'],
            'content'            => $post['content'],
            'category_id'        => $post['category_id'],
            'featured_image'     => $post['featured_image'],
            'featured_image_alt' => $post['featured_image_alt'],
            'status'             => 'draft',
            'visibility'         => $post['visibility'],
            'tags'               => array_column($post['tags'], 'id'),
        ], $websiteId, $userId);

        return $this->create($dto);
    }

    private function resolveTagIds(array $tags, int $websiteId): array
    {
        if (empty($tags)) {
            return [];
        }

        if (isset($tags[0]) && is_int($tags[0])) {
            return $tags;
        }

        return $this->tagRepository->findOrCreateByNames($tags, $websiteId);
    }
}
