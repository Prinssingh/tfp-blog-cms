<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\MediaRepository;

class MediaService
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly ImageService    $imageService,
    ) {}

    public function all(int $websiteId, array $filters = []): array
    {
        return $this->mediaRepository->all($websiteId, $filters);
    }

    public function findById(int $id, int $websiteId): array
    {
        $media = $this->mediaRepository->findById($id, $websiteId);

        if ($media === null) {
            throw new NotFoundException('Media not found.');
        }

        return $media;
    }

    public function upload(array $file, int $websiteId, int $uploadedBy, ?string $altText, ?string $caption): array
    {
        $stored = $this->imageService->store($file, $websiteId);

        $id = $this->mediaRepository->create(
            websiteId:  $websiteId,
            uploadedBy: $uploadedBy,
            fileName:   $stored['file_name'],
            filePath:   $stored['file_path'],
            mimeType:   $stored['mime_type'],
            size:       $stored['size'],
            width:      $stored['width'],
            height:     $stored['height'],
            altText:    $altText,
            caption:    $caption,
        );

        $media              = $this->mediaRepository->findById($id, $websiteId);
        $media['webp_path'] = $stored['webp_path'];
        $media['thumb_path']= $stored['thumb_path'];

        return $media;
    }

    public function updateMeta(int $id, int $websiteId, ?string $altText, ?string $caption): array
    {
        $media = $this->mediaRepository->findById($id, $websiteId);

        if ($media === null) {
            throw new NotFoundException('Media not found.');
        }

        $this->mediaRepository->updateMeta($id, $altText, $caption);
        return $this->mediaRepository->findById($id, $websiteId);
    }

    public function delete(int $id, int $websiteId): void
    {
        $media = $this->mediaRepository->findById($id, $websiteId);

        if ($media === null) {
            throw new NotFoundException('Media not found.');
        }

        $this->imageService->delete($media['file_path']);
        $this->mediaRepository->delete($id);
    }
}
