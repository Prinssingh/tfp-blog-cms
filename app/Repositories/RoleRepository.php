<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RoleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        return $this->db
            ->query('SELECT * FROM roles ORDER BY id ASC')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function permissionsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.slug
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?'
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function allPermissions(): array
    {
        return $this->db
            ->query('SELECT * FROM permissions ORDER BY slug ASC')
            ->fetchAll();
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $delete = $this->db->prepare('DELETE FROM role_permissions WHERE role_id = ?');
        $delete->execute([$roleId]);

        if (empty($permissionIds)) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)'
        );

        foreach ($permissionIds as $permissionId) {
            $insert->execute([$roleId, (int) $permissionId]);
        }
    }
}
