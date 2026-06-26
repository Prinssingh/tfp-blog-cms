<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\UpsertSeoDTO;
use PDO;

class SeoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function find(int $websiteId, string $entityType, int $entityId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM seo_metadata
             WHERE website_id = ? AND entity_type = ? AND entity_id = ?
             LIMIT 1'
        );
        $stmt->execute([$websiteId, $entityType, $entityId]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(int $websiteId, string $entityType, int $entityId, UpsertSeoDTO $dto): array
    {
        $existing = $this->find($websiteId, $entityType, $entityId);

        $schemaJson = $dto->schemaJson ? json_encode($dto->schemaJson) : null;

        if ($existing) {
            $stmt = $this->db->prepare(
                'UPDATE seo_metadata SET
                    meta_title = ?, meta_description = ?, canonical_url = ?,
                    robots = ?, focus_keyword = ?,
                    og_title = ?, og_description = ?, og_image = ?,
                    twitter_title = ?, twitter_description = ?, twitter_image = ?,
                    schema_json = ?, updated_at = NOW()
                 WHERE website_id = ? AND entity_type = ? AND entity_id = ?'
            );
            $stmt->execute([
                $dto->metaTitle, $dto->metaDescription, $dto->canonicalUrl,
                $dto->robots, $dto->focusKeyword,
                $dto->ogTitle, $dto->ogDescription, $dto->ogImage,
                $dto->twitterTitle, $dto->twitterDescription, $dto->twitterImage,
                $schemaJson,
                $websiteId, $entityType, $entityId,
            ]);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO seo_metadata
                    (website_id, entity_type, entity_id, meta_title, meta_description,
                     canonical_url, robots, focus_keyword, og_title, og_description, og_image,
                     twitter_title, twitter_description, twitter_image, schema_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                $websiteId, $entityType, $entityId,
                $dto->metaTitle, $dto->metaDescription, $dto->canonicalUrl,
                $dto->robots, $dto->focusKeyword,
                $dto->ogTitle, $dto->ogDescription, $dto->ogImage,
                $dto->twitterTitle, $dto->twitterDescription, $dto->twitterImage,
                $schemaJson,
            ]);
        }

        return $this->find($websiteId, $entityType, $entityId);
    }
}
