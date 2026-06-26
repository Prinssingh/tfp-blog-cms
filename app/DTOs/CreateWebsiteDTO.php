<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateWebsiteDTO
{
    public readonly string $name;
    public readonly string $slug;
    public readonly string $domain;
    public readonly ?string $logo;
    public readonly ?string $favicon;
    public readonly string $timezone;
    public readonly string $language;
    public readonly string $status;

    public function __construct(array $data)
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
            $errors['slug'][] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors['slug'][] = 'Slug must be lowercase alphanumeric with hyphens only.';
        }

        $domain = trim($data['domain'] ?? '');
        if (empty($domain)) {
            $errors['domain'][] = 'Domain is required.';
        }

        $status = $data['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name     = $name;
        $this->slug     = $slug;
        $this->domain   = strtolower($domain);
        $this->logo     = trim($data['logo'] ?? '') ?: null;
        $this->favicon  = trim($data['favicon'] ?? '') ?: null;
        $this->timezone = $data['timezone'] ?? 'Asia/Kolkata';
        $this->language = $data['language'] ?? 'en';
        $this->status   = $status;
    }
}
