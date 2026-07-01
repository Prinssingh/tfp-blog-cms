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
        $websites = $this->websiteRepository->all();
        return array_map([$this, 'format'], $websites);
    }

    public function findById(int $id): array
    {
        $website = $this->websiteRepository->findById($id);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        return $this->format($website);
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

        return $this->format($this->websiteRepository->findById($id));
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

        return $this->format($this->websiteRepository->findById($id));
    }

    public function delete(int $id): void
    {
        $website = $this->websiteRepository->findById($id);

        if ($website === null) {
            throw new NotFoundException('Website not found.');
        }

        $this->websiteRepository->delete($id);
    }

    public function getSettings(int $id): array
    {
        if ($this->websiteRepository->findById($id) === null) {
            throw new NotFoundException('Website not found.');
        }

        return $this->websiteRepository->getSettings($id);
    }

    public function updateSettings(int $id, array $settings): array
    {
        if ($this->websiteRepository->findById($id) === null) {
            throw new NotFoundException('Website not found.');
        }

        $current  = $this->websiteRepository->getSettings($id);
        $merged   = array_merge($current, $settings);

        $this->websiteRepository->updateSettings($id, $merged);

        return $merged;
    }

    private function format(?array $website): array
    {
        if ($website === null) {
            return [];
        }

        return [
            'id'              => $website['id'],
            'name'            => $website['name'],
            'slug'            => $website['slug'],
            'domain'          => $website['domain'],
            'subdomain'       => $website['subdomain']      ?? null,
            'description'     => $website['description']    ?? null,
            'logo_url'        => $website['logo_url']       ?? $website['logo'] ?? null,
            'favicon_url'     => $website['favicon_url']    ?? $website['favicon'] ?? null,
            'cover_image_url' => $website['cover_image_url'] ?? null,
            'theme_color'     => $website['theme_color']    ?? null,
            'accent_color'    => $website['accent_color']   ?? null,
            'timezone'        => $website['timezone'],
            'language'        => $website['language'],
            'currency'        => $website['currency']       ?? 'USD',
            'status'          => $website['status'],
            'created_at'      => $website['created_at'],
            'updated_at'      => $website['updated_at'],
        ];
    }
}
