<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreatePostDTO
{
    public readonly int $websiteId;
    public readonly int $authorId;
    public readonly int $categoryId;
    public readonly string $title;
    public readonly string $slug;
    public readonly ?string $excerpt;
    public readonly ?string $content;
    public readonly ?string $featuredImage;
    public readonly ?string $featuredImageAlt;
    public readonly string $status;
    public readonly string $visibility;
    public readonly ?string $password;
    public readonly ?string $scheduledAt;
    public readonly array $tags;

    public function __construct(array $data, int $websiteId, int $authorId)
    {
        $errors = [];

        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $errors['title'][] = 'Title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'][] = 'Title must not exceed 255 characters.';
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $errors['category_id'][] = 'Category is required.';
        }

        $status = $data['status'] ?? 'draft';
        if (!in_array($status, ['draft', 'review', 'scheduled', 'published', 'archived'], true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        $visibility = $data['visibility'] ?? 'public';
        if (!in_array($visibility, ['public', 'private', 'password'], true)) {
            $errors['visibility'][] = 'Invalid visibility value.';
        }

        if ($visibility === 'password' && empty($data['password'])) {
            $errors['password'][] = 'Password is required for password-protected posts.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
            $slug = trim($slug, '-');
        }

        $this->websiteId        = $websiteId;
        $this->authorId         = $authorId;
        $this->categoryId       = $categoryId;
        $this->title            = $title;
        $this->slug             = $slug;
        $this->excerpt          = trim($data['excerpt'] ?? '') ?: null;
        $this->content          = $data['content'] ?? null;
        $this->featuredImage    = trim($data['featured_image'] ?? '') ?: null;
        $this->featuredImageAlt = trim($data['featured_image_alt'] ?? '') ?: null;
        $this->status           = $status;
        $this->visibility       = $visibility;
        $this->password         = $visibility === 'password' ? $data['password'] : null;
        $this->scheduledAt      = $data['scheduled_at'] ?? null;
        $this->tags             = $data['tags'] ?? [];
    }
}
