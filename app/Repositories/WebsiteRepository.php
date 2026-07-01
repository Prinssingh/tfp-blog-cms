<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateWebsiteDTO;
use App\DTOs\UpdateWebsiteDTO;
use PDO;

class WebsiteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        return $this->db
            ->query('SELECT * FROM websites WHERE deleted_at IS NULL ORDER BY id ASC')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM websites WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM websites WHERE slug = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM websites WHERE domain = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$domain]);
        return $stmt->fetch() ?: null;
    }

    public function create(CreateWebsiteDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO websites
                (name, slug, domain, subdomain, description, logo_url, favicon_url, cover_image_url,
                 theme_color, accent_color, timezone, language, currency, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $dto->name,
            $dto->slug,
            $dto->domain,
            $dto->subdomain,
            $dto->description,
            $dto->logoUrl,
            $dto->faviconUrl,
            $dto->coverImageUrl,
            $dto->themeColor,
            $dto->accentColor,
            $dto->timezone,
            $dto->language,
            $dto->currency,
            $dto->status,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateWebsiteDTO $dto): void
    {
        $fields = [];
        $values = [];

        if ($dto->name          !== null) { $fields[] = 'name = ?';            $values[] = $dto->name; }
        if ($dto->domain        !== null) { $fields[] = 'domain = ?';          $values[] = $dto->domain; }
        if ($dto->subdomain     !== null) { $fields[] = 'subdomain = ?';       $values[] = $dto->subdomain; }
        if ($dto->description   !== null) { $fields[] = 'description = ?';     $values[] = $dto->description; }
        if ($dto->logoUrl       !== null) { $fields[] = 'logo_url = ?';        $values[] = $dto->logoUrl; }
        if ($dto->faviconUrl    !== null) { $fields[] = 'favicon_url = ?';     $values[] = $dto->faviconUrl; }
        if ($dto->coverImageUrl !== null) { $fields[] = 'cover_image_url = ?'; $values[] = $dto->coverImageUrl; }
        if ($dto->themeColor    !== null) { $fields[] = 'theme_color = ?';     $values[] = $dto->themeColor; }
        if ($dto->accentColor   !== null) { $fields[] = 'accent_color = ?';    $values[] = $dto->accentColor; }
        if ($dto->timezone      !== null) { $fields[] = 'timezone = ?';        $values[] = $dto->timezone; }
        if ($dto->language      !== null) { $fields[] = 'language = ?';        $values[] = $dto->language; }
        if ($dto->currency      !== null) { $fields[] = 'currency = ?';        $values[] = $dto->currency; }
        if ($dto->status        !== null) { $fields[] = 'status = ?';          $values[] = $dto->status; }
        if ($dto->settings      !== null) { $fields[] = 'settings = ?';        $values[] = json_encode($dto->settings); }

        if (empty($fields)) {
            return;
        }

        $fields[]  = 'updated_at = NOW()';
        $values[]  = $id;

        $stmt = $this->db->prepare(
            'UPDATE websites SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE websites SET deleted_at = NOW(), status = "archived", updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function domainExists(string $domain, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM websites WHERE domain = ? AND deleted_at IS NULL';
        $params = [$domain];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getSettings(int $id): array
    {
        $stmt = $this->db->prepare('SELECT settings FROM websites WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        $defaults = $this->defaultSettings();
        if (!$row || empty($row['settings'])) {
            return $defaults;
        }

        return array_merge($defaults, json_decode($row['settings'], true) ?? []);
    }

    public function updateSettings(int $id, array $settings): void
    {
        $stmt = $this->db->prepare(
            'UPDATE websites SET settings = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([json_encode($settings), $id]);
    }

    private function defaultSettings(): array
    {
        return [
            'contact_email'       => null,
            'facebook_url'        => null,
            'twitter_url'         => null,
            'linkedin_url'        => null,
            'youtube_url'         => null,
            'instagram_url'       => null,
            'google_analytics_id' => null,
            'search_console_code' => null,
            'default_meta_title'  => null,
            'default_meta_desc'   => null,
            'posts_per_page'      => 10,
            'maintenance_message' => null,
        ];
    }
}
