<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateTagDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\TagRepository;

class TagService
{
    public function __construct(
        private readonly TagRepository $tagRepository,
    ) {}

    public function all(int $websiteId, ?string $search = null): array
    {
        return $this->tagRepository->all($websiteId, $search);
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

    public function update(int $id, int $websiteId, string $name, ?string $slug = null): array
    {
        $tag = $this->tagRepository->findById($id, $websiteId);

        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }

        $slug = $slug ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        if ($slug !== $tag['slug'] && $this->tagRepository->findBySlug($slug, $websiteId) !== null) {
            throw new AppException('A tag with this slug already exists.', 409);
        }

        $this->tagRepository->update($id, $name, $slug);
        return $this->tagRepository->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId): void
    {
        $tag = $this->tagRepository->findById($id, $websiteId);

        if ($tag === null) {
            throw new NotFoundException('Tag not found.');
        }

        $this->tagRepository->delete($id);
    }
}
