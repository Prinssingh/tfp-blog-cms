<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(array $filters = []): array
    {
        $sql    = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name
                   FROM users u
                   JOIN roles r ON r.id = u.role_id
                   WHERE 1=1';
        $params = [];

        if (!empty($filters['website_id'])) {
            $sql      .= ' AND u.website_id = ?';
            $params[] = (int) $filters['website_id'];
        }

        if (!empty($filters['role'])) {
            $sql      .= ' AND r.slug = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $sql      .= ' AND u.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql      .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= ' ORDER BY u.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.status = "active"
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = "active"
             LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM users WHERE email = ?';
        $params = [$email];

        if ($excludeId !== null) {
            $sql      .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(CreateUserDTO $dto): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (website_id, role_id, name, slug, email, password, bio, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $dto->websiteId,
            $dto->roleId,
            $dto->name,
            $dto->slug,
            $dto->email,
            password_hash($dto->password, PASSWORD_BCRYPT, ['cost' => 12]),
            $dto->bio,
            $dto->status,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, UpdateUserDTO $dto): void
    {
        $fields = [];
        $values = [];

        if ($dto->name !== null) {
            $fields[] = 'name = ?';
            $values[] = $dto->name;
        }
        if ($dto->roleId !== null) {
            $fields[] = 'role_id = ?';
            $values[] = $dto->roleId;
        }
        if ($dto->websiteId !== null) {
            $fields[] = 'website_id = ?';
            $values[] = $dto->websiteId;
        }
        if ($dto->bio !== null) {
            $fields[] = 'bio = ?';
            $values[] = $dto->bio;
        }
        if ($dto->status !== null) {
            $fields[] = 'status = ?';
            $values[] = $dto->status;
        }
        if ($dto->password !== null) {
            $fields[] = 'password = ?';
            $values[] = password_hash($dto->password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields)) {
            return;
        }

        $fields[]  = 'updated_at = NOW()';
        $values[]  = $id;

        $stmt = $this->db->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public function storeRefreshToken(int $userId, string $token, int $ttl): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO refresh_tokens (user_id, token, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())'
        );
        $stmt->execute([$userId, $token, $ttl]);
    }

    public function findRefreshToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM refresh_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function deleteRefreshToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM refresh_tokens WHERE token = ?');
        $stmt->execute([$token]);
    }

    public function deleteAllRefreshTokens(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM refresh_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
