<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpsertSeoDTO
{
    public readonly ?string $metaTitle;
    public readonly ?string $metaDescription;
    public readonly ?string $canonicalUrl;
    public readonly string $robots;
    public readonly ?string $focusKeyword;
    public readonly ?string $ogTitle;
    public readonly ?string $ogDescription;
    public readonly ?string $ogImage;
    public readonly ?string $twitterTitle;
    public readonly ?string $twitterDescription;
    public readonly ?string $twitterImage;
    public readonly ?array $schemaJson;

    public function __construct(array $data)
    {
        $errors = [];

        $metaTitle = isset($data['meta_title']) ? trim($data['meta_title']) : null;
        if ($metaTitle !== null && strlen($metaTitle) > 60) {
            $errors['meta_title'][] = 'Meta title should not exceed 60 characters.';
        }

        $metaDescription = isset($data['meta_description']) ? trim($data['meta_description']) : null;
        if ($metaDescription !== null && strlen($metaDescription) > 160) {
            $errors['meta_description'][] = 'Meta description should not exceed 160 characters.';
        }

        $robots = $data['robots'] ?? 'index,follow';
        $validRobots = ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'];
        if (!in_array($robots, $validRobots, true)) {
            $errors['robots'][] = 'Invalid robots directive.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->metaTitle          = $metaTitle ?: null;
        $this->metaDescription    = $metaDescription ?: null;
        $this->canonicalUrl       = isset($data['canonical_url']) ? trim($data['canonical_url']) : null;
        $this->robots             = $robots;
        $this->focusKeyword       = isset($data['focus_keyword']) ? trim($data['focus_keyword']) : null;
        $this->ogTitle            = isset($data['og_title']) ? trim($data['og_title']) : null;
        $this->ogDescription      = isset($data['og_description']) ? trim($data['og_description']) : null;
        $this->ogImage            = isset($data['og_image']) ? trim($data['og_image']) : null;
        $this->twitterTitle       = isset($data['twitter_title']) ? trim($data['twitter_title']) : null;
        $this->twitterDescription = isset($data['twitter_description']) ? trim($data['twitter_description']) : null;
        $this->twitterImage       = isset($data['twitter_image']) ? trim($data['twitter_image']) : null;
        $this->schemaJson         = isset($data['schema_json']) ? (array) $data['schema_json'] : null;
    }
}
