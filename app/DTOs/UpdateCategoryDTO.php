<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateCategoryDTO
{
    public readonly ?string $name;
    public readonly ?string $slug;
    public readonly ?int    $parentId;
    public readonly ?int    $updatedBy;
    public readonly ?string $description;
    public readonly ?string $shortDescription;
    public readonly ?string $imageUrl;
    public readonly ?string $imageAlt;
    public readonly ?string $coverImageUrl;
    public readonly ?string $icon;
    public readonly ?int    $sortOrder;
    public readonly ?string $status;
    public readonly ?bool   $showInMenu;
    public readonly ?bool   $showInHomepage;
    public readonly ?bool   $showInFooter;
    public readonly ?bool   $showInSidebar;
    public readonly ?bool   $isFeatured;
    public readonly ?bool   $isHidden;
    public readonly ?string $seoTitle;
    public readonly ?string $seoDescription;
    public readonly ?string $focusKeyword;
    public readonly ?string $canonicalUrl;
    public readonly ?string $robotsDirective;
    public readonly ?string $ogTitle;
    public readonly ?string $ogDescription;
    public readonly ?string $ogImage;
    public readonly ?string $twitterTitle;
    public readonly ?string $twitterDescription;
    public readonly ?string $twitterImage;
    public readonly ?bool   $includeInSitemap;
    public readonly ?float  $sitemapPriority;
    public readonly ?string $changeFrequency;

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

        $validStatuses = ['active', 'hidden', 'archived'];
        $status = isset($data['status']) ? $data['status'] : null;
        if ($status !== null && !in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->updatedBy          = $userId;
        $this->name               = $name ?: null;
        $this->slug               = $slug ?: null;
        $this->parentId           = array_key_exists('parent_id', $data)
                                      ? (($data['parent_id'] !== null && $data['parent_id'] !== '') ? (int) $data['parent_id'] : null)
                                      : null;
        $this->description        = array_key_exists('description', $data)        ? (trim($data['description']) ?: null) : null;
        $this->shortDescription   = array_key_exists('short_description', $data)  ? (trim($data['short_description']) ?: null) : null;
        $this->imageUrl           = array_key_exists('image_url', $data)          ? (trim($data['image_url']) ?: null) : null;
        $this->imageAlt           = array_key_exists('image_alt', $data)          ? (trim($data['image_alt']) ?: null) : null;
        $this->coverImageUrl      = array_key_exists('cover_image_url', $data)    ? (trim($data['cover_image_url']) ?: null) : null;
        $this->icon               = array_key_exists('icon', $data)               ? (trim($data['icon']) ?: null) : null;
        $this->sortOrder          = isset($data['sort_order'])                     ? (int) $data['sort_order'] : null;
        $this->status             = $status;
        $this->showInMenu         = isset($data['show_in_menu'])         ? (bool) $data['show_in_menu'] : null;
        $this->showInHomepage     = isset($data['show_in_homepage'])     ? (bool) $data['show_in_homepage'] : null;
        $this->showInFooter       = isset($data['show_in_footer'])       ? (bool) $data['show_in_footer'] : null;
        $this->showInSidebar      = isset($data['show_in_sidebar'])      ? (bool) $data['show_in_sidebar'] : null;
        $this->isFeatured         = isset($data['is_featured'])          ? (bool) $data['is_featured'] : null;
        $this->isHidden           = isset($data['is_hidden'])            ? (bool) $data['is_hidden'] : null;
        $this->seoTitle           = array_key_exists('seo_title', $data)          ? (trim($data['seo_title']) ?: null) : null;
        $this->seoDescription     = array_key_exists('seo_description', $data)    ? (trim($data['seo_description']) ?: null) : null;
        $this->focusKeyword       = array_key_exists('focus_keyword', $data)      ? (trim($data['focus_keyword']) ?: null) : null;
        $this->canonicalUrl       = array_key_exists('canonical_url', $data)      ? (trim($data['canonical_url']) ?: null) : null;
        $this->robotsDirective    = array_key_exists('robots_directive', $data)   ? (trim($data['robots_directive']) ?: 'index, follow') : null;
        $this->ogTitle            = array_key_exists('og_title', $data)           ? (trim($data['og_title']) ?: null) : null;
        $this->ogDescription      = array_key_exists('og_description', $data)     ? (trim($data['og_description']) ?: null) : null;
        $this->ogImage            = array_key_exists('og_image', $data)           ? (trim($data['og_image']) ?: null) : null;
        $this->twitterTitle       = array_key_exists('twitter_title', $data)      ? (trim($data['twitter_title']) ?: null) : null;
        $this->twitterDescription = array_key_exists('twitter_description', $data)? (trim($data['twitter_description']) ?: null) : null;
        $this->twitterImage       = array_key_exists('twitter_image', $data)      ? (trim($data['twitter_image']) ?: null) : null;
        $this->includeInSitemap   = isset($data['include_in_sitemap'])   ? (bool) $data['include_in_sitemap'] : null;
        $this->sitemapPriority    = isset($data['sitemap_priority'])      ? min(1.0, max(0.0, (float) $data['sitemap_priority'])) : null;
        $this->changeFrequency    = array_key_exists('change_frequency', $data)   ? ($data['change_frequency'] ?: null) : null;
    }
}
