<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = "active" AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.status = "active" AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?'
        );
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
            'SELECT * FROM refresh_tokens
             WHERE token = ? AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function deleteRefreshToken(string $token): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM refresh_tokens WHERE token = ?'
        );
        $stmt->execute([$token]);
    }

    public function deleteAllRefreshTokens(int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM refresh_tokens WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
    }
}
