<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateWebsiteDTO;
use App\DTOs\UpdateWebsiteDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\WebsiteRepository;

class WebsiteService
{
    public function __construct(
        private readonly WebsiteRepository $websiteRepository,
    ) {}

    public function all(): array
    {
        return $this->websiteRepository->all();
    }

    public function findById(int $id): array
    {
        $website = $this->websiteRepository->findById($id);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        return $website;
    }

    public function create(CreateWebsiteDTO $dto): array
    {
        if ($this->websiteRepository->findBySlug($dto->slug) !== null) {
            throw new AppException('A website with this slug already exists.', 409);
        }

        if ($this->websiteRepository->domainExists($dto->domain)) {
            throw new AppException('A website with this domain already exists.', 409);
        }

        $id = $this->websiteRepository->create($dto);

        return $this->websiteRepository->findById($id);
    }

    public function update(int $id, UpdateWebsiteDTO $dto): array
    {
        $website = $this->websiteRepository->findById($id);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        if ($dto->domain !== null && $this->websiteRepository->domainExists($dto->domain, $id)) {
            throw new AppException('A website with this domain already exists.', 409);
        }

        $this->websiteRepository->update($id, $dto);

        return $this->websiteRepository->findById($id);
    }

    public function delete(int $id): void
    {
        $website = $this->websiteRepository->findById($id);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        $this->websiteRepository->delete($id);
    }
}
