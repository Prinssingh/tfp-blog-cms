<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateCategoryDTO
{
    public readonly ?string $name;
    public readonly ?string $slug;
    public readonly ?int $parentId;
    public readonly ?string $description;
    public readonly ?string $image;
    public readonly ?int $sortOrder;

    public function __construct(array $data)
    {
        $errors = [];

        $name = isset($data['name']) ? trim($data['name']) : null;
        if ($name !== null && strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $slug = isset($data['slug']) ? trim($data['slug']) : null;
        if ($slug !== null && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors['slug'][] = 'Slug must be lowercase alphanumeric with hyphens only.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name        = $name ?: null;
        $this->slug        = $slug ?: null;
        $this->parentId    = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $this->description = isset($data['description']) ? (trim($data['description']) ?: null) : null;
        $this->image       = isset($data['image']) ? (trim($data['image']) ?: null) : null;
        $this->sortOrder   = isset($data['sort_order']) ? (int) $data['sort_order'] : null;
    }
}
