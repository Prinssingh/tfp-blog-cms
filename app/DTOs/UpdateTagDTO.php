<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateTagDTO
{
    public readonly ?string $name;
    public readonly ?string $slug;
    public readonly ?int    $updatedBy;
    public readonly ?string $description;
    public readonly ?string $color;
    public readonly ?string $icon;
    public readonly ?string $status;
    public readonly ?string $seoTitle;
    public readonly ?string $seoDescription;
    public readonly ?string $focusKeyword;
    public readonly ?string $canonicalUrl;
    public readonly ?string $robotsDirective;

    public function __construct(array $data, int $userId)
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

        $color = isset($data['color']) ? trim($data['color']) : null;
        if ($color !== null && $color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'][] = 'Color must be a valid hex color (e.g. #3B82F6).';
        }

        $validStatuses = ['active', 'hidden', 'archived'];
        $status = isset($data['status']) ? $data['status'] : null;
        if ($status !== null && !in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->updatedBy       = $userId;
        $this->name            = $name ?: null;
        $this->slug            = $slug ?: null;
        $this->description     = array_key_exists('description', $data)      ? (is_string($data['description'])      ? (trim($data['description'])      ?: null) : null) : null;
        $this->color           = array_key_exists('color', $data)            ? ($color ?: null) : null;
        $this->icon            = array_key_exists('icon', $data)             ? (is_string($data['icon'])             ? (trim($data['icon'])             ?: null) : null) : null;
        $this->status          = $status;
        $this->seoTitle        = array_key_exists('seo_title', $data)        ? (is_string($data['seo_title'])        ? (trim($data['seo_title'])        ?: null) : null) : null;
        $this->seoDescription  = array_key_exists('seo_description', $data)  ? (is_string($data['seo_description'])  ? (trim($data['seo_description'])  ?: null) : null) : null;
        $this->focusKeyword    = array_key_exists('focus_keyword', $data)    ? (is_string($data['focus_keyword'])    ? (trim($data['focus_keyword'])    ?: null) : null) : null;
        $this->canonicalUrl    = array_key_exists('canonical_url', $data)    ? (is_string($data['canonical_url'])    ? (trim($data['canonical_url'])    ?: null) : null) : null;
        $this->robotsDirective = array_key_exists('robots_directive', $data) ? (is_string($data['robots_directive']) ? (trim($data['robots_directive']) ?: null) : null) : null;
    }
}
