<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdatePostDTO
{
    public readonly int     $editorId;
    public readonly ?int    $categoryId;
    public readonly ?string $title;
    public readonly ?string $subtitle;
    public readonly ?string $slug;
    public readonly ?string $excerpt;
    public readonly ?string $summary;
    public readonly ?string $contentHtml;
    public readonly ?string $contentJson;
    public readonly ?string $status;
    public readonly ?string $visibility;
    public readonly ?string $priority;
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
    public readonly ?string $robotsDirective;
    public readonly ?string $ogTitle;
    public readonly ?string $ogDescription;
    public readonly ?string $ogImage;
    public readonly ?string $twitterTitle;
    public readonly ?string $twitterDescription;
    public readonly ?string $twitterImage;
    // Flags
    public readonly ?bool $isFeatured;
    public readonly ?bool $isSticky;
    public readonly ?bool $showOnHomepage;
    public readonly ?bool $includeInSitemap;
    public readonly ?bool $includeInRss;
    // Relations
    public readonly ?array $tags;
    public readonly ?array $categoryIds;
    // Notes
    public readonly ?string $reviewNotes;
    public readonly ?string $editorNotes;
    public readonly ?string $internalNotes;
    public readonly ?string $rejectionReason;

    public function __construct(array $data, int $editorId)
    {
        $errors = [];

        $title = isset($data['title']) ? trim($data['title']) : null;
        if ($title !== null && strlen($title) > 255) {
            $errors['title'][] = 'Title must not exceed 255 characters.';
        }
        if ($title === '') {
            $title = null;
        }

        $validStatuses = ['draft', 'review_requested', 'in_review', 'approved', 'scheduled', 'published', 'archived', 'rejected', 'deleted'];
        $status = $data['status'] ?? null;
        if ($status !== null && !in_array($status, $validStatuses, true)) {
            $errors['status'][] = 'Invalid status value.';
        }

        $visibility = $data['visibility'] ?? null;
        if ($visibility !== null && !in_array($visibility, ['public', 'private', 'password', 'members_only'], true)) {
            $errors['visibility'][] = 'Invalid visibility value.';
        }

        $priority = $data['priority'] ?? null;
        if ($priority !== null && !in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $errors['priority'][] = 'Invalid priority value.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->editorId   = $editorId;
        $this->categoryId = array_key_exists('category_id', $data)
            ? ($data['category_id'] > 0 ? (int) $data['category_id'] : null)
            : null;
        $this->title      = $title;
        $this->subtitle   = array_key_exists('subtitle', $data) ? (trim($data['subtitle']) ?: null) : null;
        $this->slug       = isset($data['slug']) ? (trim($data['slug']) ?: null) : null;
        $this->excerpt    = array_key_exists('excerpt', $data) ? (trim($data['excerpt']) ?: null) : null;
        $this->summary    = array_key_exists('summary', $data) ? (trim($data['summary']) ?: null) : null;
        $this->contentHtml = $data['content'] ?? $data['content_html'] ?? null;
        $this->contentJson = array_key_exists('content_json', $data)
            ? (is_array($data['content_json']) ? json_encode($data['content_json']) : $data['content_json'])
            : null;
        $this->status         = $status;
        $this->visibility     = $visibility;
        $this->priority       = $priority;
        $this->password       = $data['password'] ?? null;
        $this->scheduledAt    = $data['scheduled_at'] ?? null;
        $this->language       = $data['language'] ?? null;
        $this->featuredImage    = array_key_exists('featured_image', $data) ? (trim($data['featured_image']) ?: null) : null;
        $this->featuredImageAlt = array_key_exists('featured_image_alt', $data) ? (trim($data['featured_image_alt']) ?: null) : null;
        $this->featuredImageCaption = array_key_exists('featured_image_caption', $data) ? (trim($data['featured_image_caption']) ?: null) : null;
        $this->featuredImageCredit  = array_key_exists('featured_image_credit', $data)  ? (trim($data['featured_image_credit'])  ?: null) : null;
        $this->seoTitle           = array_key_exists('seo_title', $data)           ? (trim($data['seo_title'])           ?: null) : null;
        $this->seoDescription     = array_key_exists('seo_description', $data)     ? (trim($data['seo_description'])     ?: null) : null;
        $this->focusKeyword       = array_key_exists('focus_keyword', $data)       ? (trim($data['focus_keyword'])       ?: null) : null;
        $this->canonicalUrl       = array_key_exists('canonical_url', $data)       ? (trim($data['canonical_url'])       ?: null) : null;
        $this->robotsDirective    = $data['robots_directive'] ?? null;
        $this->ogTitle            = array_key_exists('og_title', $data)            ? (trim($data['og_title'])            ?: null) : null;
        $this->ogDescription      = array_key_exists('og_description', $data)      ? (trim($data['og_description'])      ?: null) : null;
        $this->ogImage            = array_key_exists('og_image', $data)            ? (trim($data['og_image'])            ?: null) : null;
        $this->twitterTitle       = array_key_exists('twitter_title', $data)       ? (trim($data['twitter_title'])       ?: null) : null;
        $this->twitterDescription = array_key_exists('twitter_description', $data) ? (trim($data['twitter_description']) ?: null) : null;
        $this->twitterImage       = array_key_exists('twitter_image', $data)       ? (trim($data['twitter_image'])       ?: null) : null;
        $this->isFeatured     = array_key_exists('is_featured', $data)     ? (bool) $data['is_featured']     : null;
        $this->isSticky       = array_key_exists('is_sticky', $data)       ? (bool) $data['is_sticky']       : null;
        $this->showOnHomepage = array_key_exists('show_on_homepage', $data) ? (bool) $data['show_on_homepage'] : null;
        $this->includeInSitemap = array_key_exists('include_in_sitemap', $data) ? (bool) $data['include_in_sitemap'] : null;
        $this->includeInRss     = array_key_exists('include_in_rss', $data)     ? (bool) $data['include_in_rss']     : null;
        $this->tags          = array_key_exists('tags', $data) ? $data['tags'] : (array_key_exists('tag_ids', $data) ? $data['tag_ids'] : null);
        $this->categoryIds   = $data['category_ids'] ?? null;
        $this->reviewNotes    = array_key_exists('review_notes', $data)    ? (trim($data['review_notes'])    ?: null) : null;
        $this->editorNotes    = array_key_exists('editor_notes', $data)    ? (trim($data['editor_notes'])    ?: null) : null;
        $this->internalNotes  = array_key_exists('internal_notes', $data)  ? (trim($data['internal_notes'])  ?: null) : null;
        $this->rejectionReason = array_key_exists('rejection_reason', $data) ? (trim($data['rejection_reason']) ?: null) : null;
    }
}
