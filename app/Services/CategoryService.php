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

        $this->categoryRepository->update($id, $dto);
        return $this->categoryRepository->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId): void
    {
        $category = $this->categoryRepository->findById($id, $websiteId);

        if ($category === null) {
            throw new NotFoundException('Category not found.');
        }

        $this->categoryRepository->delete($id);
    }
}
