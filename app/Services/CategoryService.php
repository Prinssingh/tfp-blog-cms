<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateCategoryDTO;
use App\DTOs\UpdateCategoryDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\CategoryRepository;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function all(int $websiteId, array $filters = []): array
    {
        return $this->categoryRepository->all($websiteId, $filters);
    }

    public function trash(int $websiteId): array
    {
        return $this->categoryRepository->trash($websiteId);
    }

    public function findById(int $id, int $websiteId): array
    {
        $category = $this->categoryRepository->findById($id, $websiteId);
        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }
        return $category;
    }

    public function create(CreateCategoryDTO $dto): array
    {
        if ($this->categoryRepository->findBySlug($dto->slug, $dto->websiteId) !== null) {
            throw new AppException('A category with this slug already exists.', 409);
        }

        if ($dto->parentId !== null) {
            $parent = $this->categoryRepository->findById($dto->parentId, $dto->websiteId);
            if ($parent === null) {
                throw new AppException('Parent category not found.', 422);
            }
            if (($parent['depth'] ?? 0) >= 2) {
                throw new AppException('Maximum category depth of 3 levels exceeded.', 422);
            }
        }

        $id = $this->categoryRepository->create($dto);
        return $this->categoryRepository->findById($id, $dto->websiteId);
    }

    public function update(int $id, int $websiteId, UpdateCategoryDTO $dto): array
    {
        $category = $this->categoryRepository->findById($id, $websiteId);
        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }

        if ($dto->slug !== null && $dto->slug !== $category['slug']) {
            $existing = $this->categoryRepository->findBySlug($dto->slug, $websiteId);
            if ($existing !== null) {
                throw new AppException('A category with this slug already exists.', 409);
            }
        }

        if ($dto->parentId !== null && $dto->parentId !== (int) $category['parent_id']) {
            if ($dto->parentId === $id) {
                throw new AppException('A category cannot be its own parent.', 422);
            }
            $parent = $this->categoryRepository->findById($dto->parentId, $websiteId);
            if ($parent === null) {
                throw new AppException('Parent category not found.', 422);
            }
            if (($parent['depth'] ?? 0) >= 2) {
                throw new AppException('Maximum category depth of 3 levels exceeded.', 422);
            }
        }

        $this->categoryRepository->update($id, $dto);
        return $this->categoryRepository->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId, int $userId): void
    {
        $category = $this->categoryRepository->findById($id, $websiteId);
        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }
        $this->categoryRepository->softDelete($id, $userId);
    }

    public function restore(int $id, int $websiteId): array
    {
        $category = $this->categoryRepository->findById($id, $websiteId, includeDeleted: true);
        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }
        $this->categoryRepository->restore($id);
        return $this->categoryRepository->findById($id, $websiteId);
    }

    public function forceDelete(int $id, int $websiteId): void
    {
        $category = $this->categoryRepository->findById($id, $websiteId, includeDeleted: true);
        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }
        $this->categoryRepository->forceDelete($id);
    }

    public function merge(int $sourceId, int $targetId, int $websiteId): array
    {
        if ($sourceId === $targetId) {
            throw new AppException('Source and target categories must be different.', 422);
        }
        if ($this->categoryRepository->findById($sourceId, $websiteId) === null) {
            throw new NotFoundException('Source category not found.');
        }
        if ($this->categoryRepository->findById($targetId, $websiteId) === null) {
            throw new NotFoundException('Target category not found.');
        }

        $this->categoryRepository->merge($sourceId, $targetId, $websiteId);
        return $this->categoryRepository->findById($targetId, $websiteId);
    }
}
