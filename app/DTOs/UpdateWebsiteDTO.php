<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateWebsiteDTO
{
    public readonly ?string $name;
    public readonly ?string $domain;
    public readonly ?string $subdomain;
    public readonly ?string $description;
    public readonly ?string $logoUrl;
    public readonly ?string $faviconUrl;
    public readonly ?string $coverImageUrl;
    public readonly ?string $themeColor;
    public readonly ?string $accentColor;
    public readonly ?string $timezone;
    public readonly ?string $language;
    public readonly ?string $currency;
    public readonly ?string $status;
    public readonly ?array  $settings;

    public function __construct(array $data)
    {
        $errors = [];

        $name = isset($data['name']) ? trim($data['name']) : null;
        if ($name !== null && strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $status = $data['status'] ?? null;
        if ($status !== null && !in_array($status, ['active', 'maintenance', 'suspended', 'archived', 'inactive'], true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name          = $name ?: null;
        $this->domain        = isset($data['domain'])        ? strtolower(trim($data['domain']))          : null;
        $this->subdomain     = isset($data['subdomain'])     ? (trim($data['subdomain'])     ?: null)     : null;
        $this->description   = isset($data['description'])   ? (trim($data['description'])   ?: null)     : null;
        $this->logoUrl       = isset($data['logo_url'])      ? (trim($data['logo_url'])      ?: null)     : null;
        $this->faviconUrl    = isset($data['favicon_url'])   ? (trim($data['favicon_url'])   ?: null)     : null;
        $this->coverImageUrl = isset($data['cover_image_url']) ? (trim($data['cover_image_url']) ?: null) : null;
        $this->themeColor    = isset($data['theme_color'])   ? (trim($data['theme_color'])   ?: null)     : null;
        $this->accentColor   = isset($data['accent_color'])  ? (trim($data['accent_color'])  ?: null)     : null;
        $this->timezone      = $data['timezone'] ?? null;
        $this->language      = $data['language'] ?? null;
        $this->currency      = $data['currency'] ?? null;
        $this->status        = $status;
        $this->settings      = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : null;
    }
}
