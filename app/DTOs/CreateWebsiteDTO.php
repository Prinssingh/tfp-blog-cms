<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateWebsiteDTO
{
    public readonly string  $name;
    public readonly string  $slug;
    public readonly string  $domain;
    public readonly ?string $subdomain;
    public readonly ?string $description;
    public readonly ?string $logoUrl;
    public readonly ?string $faviconUrl;
    public readonly ?string $coverImageUrl;
    public readonly ?string $themeColor;
    public readonly ?string $accentColor;
    public readonly string  $timezone;
    public readonly string  $language;
    public readonly string  $currency;
    public readonly string  $status;

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
        if (empty($slug) && !empty($name)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
        }
        if (!empty($slug) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors['slug'][] = 'Slug must be lowercase alphanumeric with hyphens only.';
        }

        $domain = trim($data['domain'] ?? '');
        if (empty($domain)) {
            $errors['domain'][] = 'Domain is required.';
        }

        $status = $data['status'] ?? 'active';
        if (!in_array($status, ['active', 'maintenance', 'suspended', 'archived', 'inactive'], true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name          = $name;
        $this->slug          = $slug ?: $name;
        $this->domain        = strtolower($domain);
        $this->subdomain     = trim($data['subdomain']      ?? '') ?: null;
        $this->description   = trim($data['description']    ?? '') ?: null;
        $this->logoUrl       = trim($data['logo_url']       ?? '') ?: null;
        $this->faviconUrl    = trim($data['favicon_url']    ?? '') ?: null;
        $this->coverImageUrl = trim($data['cover_image_url'] ?? '') ?: null;
        $this->themeColor    = trim($data['theme_color']    ?? '') ?: null;
        $this->accentColor   = trim($data['accent_color']   ?? '') ?: null;
        $this->timezone      = $data['timezone'] ?? 'Asia/Kolkata';
        $this->language      = $data['language'] ?? 'en';
        $this->currency      = $data['currency'] ?? 'USD';
        $this->status        = $status;
    }
}
