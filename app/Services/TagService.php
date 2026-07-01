<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateTagDTO;
use App\DTOs\UpdateTagDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\TagRepository;

class TagService
{
    public function __construct(
        private readonly TagRepository $tagRepository,
    ) {}

    public function all(int $websiteId, array $filters = []): array
    {
        return $this->tagRepository->all($websiteId, $filters);
    }

    public function trash(int $websiteId): array
    {
        return $this->tagRepository->trash($websiteId);
    }

    public function findById(int $id, int $websiteId): array
    {
        $tag = $this->tagRepository->findById($id, $websiteId);
        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }
        return $tag;
    }

    public function create(CreateTagDTO $dto): array
    {
        if ($this->tagRepository->findBySlug($dto->slug, $dto->websiteId) !== null) {
            throw new AppException('A tag with this slug already exists.', 409);
        }
        $id = $this->tagRepository->create($dto);
        return $this->tagRepository->findById($id, $dto->websiteId);
    }

    public function update(int $id, int $websiteId, UpdateTagDTO $dto): array
    {
        $tag = $this->tagRepository->findById($id, $websiteId);
        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }

        if ($dto->slug !== null && $dto->slug !== $tag['slug']) {
            if ($this->tagRepository->findBySlug($dto->slug, $websiteId) !== null) {
                throw new AppException('A tag with this slug already exists.', 409);
            }
        }

        $this->tagRepository->update($id, $dto);
        return $this->tagRepository->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId, int $userId): void
    {
        $tag = $this->tagRepository->findById($id, $websiteId);
        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }
        $this->tagRepository->softDelete($id, $userId);
    }

    public function restore(int $id, int $websiteId): array
    {
        $tag = $this->tagRepository->findById($id, $websiteId, includeDeleted: true);
        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }
        $this->tagRepository->restore($id);
        return $this->tagRepository->findById($id, $websiteId);
    }

    public function forceDelete(int $id, int $websiteId): void
    {
        $tag = $this->tagRepository->findById($id, $websiteId, includeDeleted: true);
        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }
        $this->tagRepository->forceDelete($id);
    }

    public function merge(int $sourceId, int $targetId, int $websiteId): array
    {
        if ($sourceId === $targetId) {
            throw new AppException('Source and target tags must be different.', 422);
        }
        if ($this->tagRepository->findById($sourceId, $websiteId) === null) {
            throw new NotFoundException('Source tag not found.');
        }
        if ($this->tagRepository->findById($targetId, $websiteId) === null) {
            throw new NotFoundException('Target tag not found.');
        }

        $this->tagRepository->merge($sourceId, $targetId, $websiteId);
        return $this->tagRepository->findById($targetId, $websiteId);
    }
}
