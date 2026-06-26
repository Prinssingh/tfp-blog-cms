<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdatePostDTO
{
    public readonly ?int $categoryId;
    public readonly ?string $title;
    public readonly ?string $slug;
    public readonly ?string $excerpt;
    public readonly ?string $content;
    public readonly ?string $featuredImage;
    public readonly ?string $featuredImageAlt;
    public readonly ?string $status;
    public readonly ?string $visibility;
    public readonly ?string $password;
    public readonly ?string $scheduledAt;
    public readonly ?array $tags;
    public readonly ?int $editorId;

    public function __construct(array $data, int $editorId)
    {
        $errors = [];

        $title = isset($data['title']) ? trim($data['title']) : null;
        if ($title !== null && strlen($title) > 255) {
            $errors['title'][] = 'Title must not exceed 255 characters.';
        }

        $status = $data['status'] ?? null;
        if ($status !== null && !in_array($status, ['draft', 'review', 'scheduled', 'published', 'archived'], true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        $visibility = $data['visibility'] ?? null;
        if ($visibility !== null && !in_array($visibility, ['public', 'private', 'password'], true)) {
            $errors['visibility'][] = 'Invalid visibility value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->categoryId       = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $this->title            = $title ?: null;
        $this->slug             = isset($data['slug']) ? trim($data['slug']) : null;
        $this->excerpt          = isset($data['excerpt']) ? (trim($data['excerpt']) ?: null) : null;
        $this->content          = $data['content'] ?? null;
        $this->featuredImage    = isset($data['featured_image']) ? (trim($data['featured_image']) ?: null) : null;
        $this->featuredImageAlt = isset($data['featured_image_alt']) ? (trim($data['featured_image_alt']) ?: null) : null;
        $this->status           = $status;
        $this->visibility       = $visibility;
        $this->password         = $data['password'] ?? null;
        $this->scheduledAt      = $data['scheduled_at'] ?? null;
        $this->tags             = $data['tags'] ?? null;
        $this->editorId         = $editorId;
    }
}
