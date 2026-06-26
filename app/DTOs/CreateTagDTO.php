<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateTagDTO
{
    public readonly int $websiteId;
    public readonly string $name;
    public readonly string $slug;

    public function __construct(array $data, int $websiteId)
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'][] = 'Name is required.';
        } elseif (strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors['slug'][] = 'Slug must be lowercase alphanumeric with hyphens only.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->websiteId = $websiteId;
        $this->name      = $name;
        $this->slug      = $slug;
    }
}
