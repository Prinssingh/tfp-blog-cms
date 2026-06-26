<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateCategoryDTO;
use App\DTOs\UpdateCategoryDTO;
use PDO;

class CategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT c.*, p.name AS parent_name
                   FROM categories c
                   LEFT JOIN categories p ON p.id = c.parent_id
                   WHERE c.website_id = ?';
        $params = [$websiteId];

        if (isset($filters['parent_id'])) {
            $sql      .= ' AND c.parent_id = ?';
            $params[] = (int) $filters['parent_id'];
        }

        if (!empty($filters['search'])) {
            $sql      .= ' AND c.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id
             WHERE c.id = ? AND c.website_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categories WHERE slug = ? AND website_id = ? LIMIT 1'
        );
        $stmt->execute([$slug, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function create(CreateCategoryDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (website_id, parent_id, name, slug, description, image, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $dto->websiteId,
            $dto->parentId,
            $dto->name,
            $dto->slug,
            $dto->description,
            $dto->image,
            $dto->sortOrder,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateCategoryDTO $dto): void
    {
        $fields = [];
        $values = [];

        if ($dto->name !== null)        { $fields[] = 'name = ?';        $values[] = $dto->name; }
        if ($dto->slug !== null)        { $fields[] = 'slug = ?';        $values[] = $dto->slug; }
        if ($dto->parentId !== null)    { $fields[] = 'parent_id = ?';   $values[] = $dto->parentId; }
        if ($dto->description !== null) { $fields[] = 'description = ?'; $values[] = $dto->description; }
        if ($dto->image !== null)       { $fields[] = 'image = ?';       $values[] = $dto->image; }
        if ($dto->sortOrder !== null)   { $fields[] = 'sort_order = ?';  $values[] = $dto->sortOrder; }

        if (empty($fields)) {
            return;
        }

        $fields[]  = 'updated_at = NOW()';
        $values[]  = $id;

        $stmt = $this->db->prepare(
            'UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
    }
}
