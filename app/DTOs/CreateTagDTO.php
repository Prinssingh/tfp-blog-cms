<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateTagDTO
{
    public readonly int     $websiteId;
    public readonly int     $createdBy;
    public readonly string  $name;
    public readonly string  $slug;
    public readonly ?string $description;
    public readonly ?string $color;
    public readonly ?string $icon;
    public readonly string  $status;
    public readonly ?string $seoTitle;
    public readonly ?string $seoDescription;
    public readonly ?string $focusKeyword;
    public readonly ?string $canonicalUrl;
    public readonly string  $robotsDirective;

    public function __construct(array $data, int $websiteId, int $userId = 0)
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

        $color = trim($data['color'] ?? '');
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'][] = 'Color must be a valid hex color (e.g. #3B82F6).';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->websiteId       = $websiteId;
        $this->createdBy       = $userId;
        $this->name            = $name;
        $this->slug            = $slug;
        $this->description     = trim($data['description'] ?? '') ?: null;
        $this->color           = $color ?: null;
        $this->icon            = trim($data['icon'] ?? '') ?: null;
        $this->status          = in_array($data['status'] ?? '', ['active','hidden','archived'], true) ? $data['status'] : 'active';
        $this->seoTitle        = trim($data['seo_title'] ?? '') ?: null;
        $this->seoDescription  = trim($data['seo_description'] ?? '') ?: null;
        $this->focusKeyword    = trim($data['focus_keyword'] ?? '') ?: null;
        $this->canonicalUrl    = trim($data['canonical_url'] ?? '') ?: null;
        $this->robotsDirective = trim($data['robots_directive'] ?? '') ?: 'index, follow';
    }
}
