<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreatePostDTO
{
    public readonly int     $websiteId;
    public readonly int     $authorId;
    public readonly ?int    $categoryId;
    public readonly string  $title;
    public readonly ?string $subtitle;
    public readonly string  $slug;
    public readonly ?string $excerpt;
    public readonly ?string $summary;
    public readonly ?string $contentHtml;
    public readonly ?string $contentJson;
    public readonly string  $status;
    public readonly string  $visibility;
    public readonly string  $priority;
    public readonly ?string $password;
    public readonly ?string $scheduledAt;
    public readonly ?string $language;
    // Featured image
    public readonly ?string $featuredImage;
    public readonly ?string $featuredImageAlt;
    public readonly ?string $featuredImageCaption;
    public readonly ?string $featuredImageCredit;
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
    // Flags
    public readonly bool    $isFeatured;
    public readonly bool    $isSticky;
    public readonly bool    $showOnHomepage;
    public readonly bool    $includeInSitemap;
    public readonly bool    $includeInRss;
    // Relations
    public readonly array   $tags;
    public readonly array   $categoryIds;
    // Notes
    public readonly ?string $internalNotes;

    public function __construct(array $data, int $websiteId, int $authorId)
    {
        $errors = [];

        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            $errors['title'][] = 'Title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'][] = 'Title must not exceed 255 characters.';
        }

        $validStatuses = ['draft', 'review_requested', 'in_review', 'approved', 'scheduled', 'published', 'archived'];
        $status = $data['status'] ?? 'draft';
        if (!in_array($status, $validStatuses, true)) {
            $status = 'draft';
        }

        $visibility = $data['visibility'] ?? 'public';
        if (!in_array($visibility, ['public', 'private', 'password', 'members_only'], true)) {
            $visibility = 'public';
        }

        if ($visibility === 'password' && empty($data['password'])) {
            $errors['password'][] = 'Password is required for password-protected posts.';
        }

        $priority = $data['priority'] ?? 'normal';
        if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $priority = 'normal';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
            $slug = trim($slug, '-') . '-' . time();
        }

        $this->websiteId    = $websiteId;
        $this->authorId     = $authorId;
        $this->categoryId   = isset($data['category_id']) && $data['category_id'] > 0
            ? (int) $data['category_id'] : null;
        $this->title        = $title;
        $this->subtitle     = trim($data['subtitle'] ?? '') ?: null;
        $this->slug         = $slug;
        $this->excerpt      = trim($data['excerpt'] ?? '') ?: null;
        $this->summary      = trim($data['summary'] ?? '') ?: null;
        $this->contentHtml  = $data['content'] ?? $data['content_html'] ?? null;
        $this->contentJson  = is_array($data['content_json'] ?? null)
            ? json_encode($data['content_json'])
            : ($data['content_json'] ?? null);
        $this->status           = $status;
        $this->visibility       = $visibility;
        $this->priority         = $priority;
        $this->password         = $visibility === 'password' ? ($data['password'] ?? null) : null;
        $this->scheduledAt      = $data['scheduled_at'] ?? null;
        $this->language         = $data['language'] ?? 'en';
        $this->featuredImage    = trim($data['featured_image'] ?? '') ?: null;
        $this->featuredImageAlt = trim($data['featured_image_alt'] ?? '') ?: null;
        $this->featuredImageCaption = trim($data['featured_image_caption'] ?? '') ?: null;
        $this->featuredImageCredit  = trim($data['featured_image_credit'] ?? '') ?: null;
        $this->seoTitle             = trim($data['seo_title'] ?? '') ?: null;
        $this->seoDescription       = trim($data['seo_description'] ?? '') ?: null;
        $this->focusKeyword         = trim($data['focus_keyword'] ?? '') ?: null;
        $this->canonicalUrl         = trim($data['canonical_url'] ?? '') ?: null;
        $this->robotsDirective      = $data['robots_directive'] ?? 'index, follow';
        $this->ogTitle              = trim($data['og_title'] ?? '') ?: null;
        $this->ogDescription        = trim($data['og_description'] ?? '') ?: null;
        $this->ogImage              = trim($data['og_image'] ?? '') ?: null;
        $this->twitterTitle         = trim($data['twitter_title'] ?? '') ?: null;
        $this->twitterDescription   = trim($data['twitter_description'] ?? '') ?: null;
        $this->twitterImage         = trim($data['twitter_image'] ?? '') ?: null;
        $this->isFeatured           = (bool) ($data['is_featured'] ?? false);
        $this->isSticky             = (bool) ($data['is_sticky'] ?? false);
        $this->showOnHomepage       = (bool) ($data['show_on_homepage'] ?? false);
        $this->includeInSitemap     = (bool) ($data['include_in_sitemap'] ?? true);
        $this->includeInRss         = (bool) ($data['include_in_rss'] ?? true);
        $this->tags                 = $data['tags'] ?? $data['tag_ids'] ?? [];
        $this->categoryIds          = $data['category_ids'] ?? [];
        $this->internalNotes        = trim($data['internal_notes'] ?? '') ?: null;
    }
}
