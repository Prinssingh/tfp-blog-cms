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
            ->query('SELECT * FROM websites ORDER BY id ASC')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM websites WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM websites WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function findByDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM websites WHERE domain = ? LIMIT 1');
        $stmt->execute([$domain]);
        return $stmt->fetch() ?: null;
    }

    public function create(CreateWebsiteDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO websites (name, slug, domain, logo, favicon, timezone, language, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $dto->name,
            $dto->slug,
            $dto->domain,
            $dto->logo,
            $dto->favicon,
            $dto->timezone,
            $dto->language,
            $dto->status,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateWebsiteDTO $dto): void
    {
        $fields = [];
        $values = [];

        if ($dto->name !== null)     { $fields[] = 'name = ?';     $values[] = $dto->name; }
        if ($dto->domain !== null)   { $fields[] = 'domain = ?';   $values[] = $dto->domain; }
        if ($dto->logo !== null)     { $fields[] = 'logo = ?';     $values[] = $dto->logo; }
        if ($dto->favicon !== null)  { $fields[] = 'favicon = ?';  $values[] = $dto->favicon; }
        if ($dto->timezone !== null) { $fields[] = 'timezone = ?'; $values[] = $dto->timezone; }
        if ($dto->language !== null) { $fields[] = 'language = ?'; $values[] = $dto->language; }
        if ($dto->status !== null)   { $fields[] = 'status = ?';   $values[] = $dto->status; }

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
        $stmt = $this->db->prepare('DELETE FROM websites WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM websites WHERE slug = ?';
        $params = [$slug];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return (int) $this->db->prepare($sql)->execute($params) > 0
            || (int) $this->db->query("SELECT FOUND_ROWS()")->fetchColumn() > 0;
    }

    public function domainExists(string $domain, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM websites WHERE domain = ?';
        $params = [$domain];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
