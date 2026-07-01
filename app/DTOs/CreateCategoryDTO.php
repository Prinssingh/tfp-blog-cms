<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateCategoryDTO
{
    public readonly int     $websiteId;
    public readonly ?int    $parentId;
    public readonly int     $createdBy;
    public readonly string  $name;
    public readonly string  $slug;
    public readonly ?string $description;
    public readonly ?string $shortDescription;
    public readonly ?string $imageUrl;
    public readonly ?string $imageAlt;
    public readonly ?string $coverImageUrl;
    public readonly ?string $icon;
    public readonly int     $sortOrder;
    public readonly string  $status;

    // Display settings
    public readonly bool $showInMenu;
    public readonly bool $showInHomepage;
    public readonly bool $showInFooter;
    public readonly bool $showInSidebar;
    public readonly bool $isFeatured;
    public readonly bool $isHidden;

    // SEO
    public readonly ?string $seoTitle;
    public readonly ?string $seoDescription;
    public readonly ?string $focusKeyword;
    public readonly ?string $canonicalUrl;
    public readonly string  $robotsDirective;
    public readonly ?string $ogTitle;
    public readonly ?string $ogDescription;
    public readonly ?string $ogImage;
    public readonly ?string $twitterTitle;
    public readonly ?string $twitterDescription;
    public readonly ?string $twitterImage;

    // Sitemap
    public readonly bool   $includeInSitemap;
    public readonly float  $sitemapPriority;
    public readonly string $changeFrequency;

    public function __construct(array $data, int $websiteId, int $userId)
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

        $validStatuses = ['active', 'hidden', 'archived'];
        $status = $data['status'] ?? 'active';
        if (!in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        $validFreqs = ['always','hourly','daily','weekly','monthly','yearly','never'];
        $changeFrequency = $data['change_frequency'] ?? 'weekly';
        if (!in_array($changeFrequency, $validFreqs, true)) {
            $changeFrequency = 'weekly';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->websiteId          = $websiteId;
        $this->createdBy          = $userId;
        $this->parentId           = isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $this->name               = $name;
        $this->slug               = $slug;
        $this->description        = trim($data['description'] ?? '') ?: null;
        $this->shortDescription   = trim($data['short_description'] ?? '') ?: null;
        $this->imageUrl           = trim($data['image_url'] ?? '') ?: null;
        $this->imageAlt           = trim($data['image_alt'] ?? '') ?: null;
        $this->coverImageUrl      = trim($data['cover_image_url'] ?? '') ?: null;
        $this->icon               = trim($data['icon'] ?? '') ?: null;
        $this->sortOrder          = (int) ($data['sort_order'] ?? 0);
        $this->status             = $status;
        $this->showInMenu         = (bool) ($data['show_in_menu'] ?? true);
        $this->showInHomepage     = (bool) ($data['show_in_homepage'] ?? false);
        $this->showInFooter       = (bool) ($data['show_in_footer'] ?? false);
        $this->showInSidebar      = (bool) ($data['show_in_sidebar'] ?? false);
        $this->isFeatured         = (bool) ($data['is_featured'] ?? false);
        $this->isHidden           = (bool) ($data['is_hidden'] ?? false);
        $this->seoTitle           = trim($data['seo_title'] ?? '') ?: null;
        $this->seoDescription     = trim($data['seo_description'] ?? '') ?: null;
        $this->focusKeyword       = trim($data['focus_keyword'] ?? '') ?: null;
        $this->canonicalUrl       = trim($data['canonical_url'] ?? '') ?: null;
        $this->robotsDirective    = trim($data['robots_directive'] ?? '') ?: 'index, follow';
        $this->ogTitle            = trim($data['og_title'] ?? '') ?: null;
        $this->ogDescription      = trim($data['og_description'] ?? '') ?: null;
        $this->ogImage            = trim($data['og_image'] ?? '') ?: null;
        $this->twitterTitle       = trim($data['twitter_title'] ?? '') ?: null;
        $this->twitterDescription = trim($data['twitter_description'] ?? '') ?: null;
        $this->twitterImage       = trim($data['twitter_image'] ?? '') ?: null;
        $this->includeInSitemap   = (bool) ($data['include_in_sitemap'] ?? true);
        $this->sitemapPriority    = min(1.0, max(0.0, (float) ($data['sitemap_priority'] ?? 0.5)));
        $this->changeFrequency    = $changeFrequency;
    }
}
