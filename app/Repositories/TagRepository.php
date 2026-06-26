<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateTagDTO;
use PDO;

class TagRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(int $websiteId, ?string $search = null): array
    {
        $sql    = 'SELECT * FROM tags WHERE website_id = ?';
        $params = [$websiteId];

        if (!empty($search)) {
            $sql      .= ' AND name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tags WHERE id = ? AND website_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tags WHERE slug = ? AND website_id = ? LIMIT 1'
        );
        $stmt->execute([$slug, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findOrCreateByNames(array $names, int $websiteId): array
    {
        $ids = [];

        foreach ($names as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');

            $existing = $this->findBySlug($slug, $websiteId);

            if ($existing) {
                $ids[] = $existing['id'];
            } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO tags (website_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$websiteId, $name, $slug]);
                $ids[] = (int) $this->db->lastInsertId();
            }
        }

        return $ids;
    }

    public function create(CreateTagDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tags (website_id, name, slug, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$dto->websiteId, $dto->name, $dto->slug]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $slug): void
    {
        $stmt = $this->db->prepare(
            'UPDATE tags SET name = ?, slug = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$name, $slug, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM tags WHERE id = ?');
        $stmt->execute([$id]);
    }
}
