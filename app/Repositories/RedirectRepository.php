<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RedirectRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(int $websiteId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM redirects WHERE website_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$websiteId]);
        return $stmt->fetchAll();
    }

    public function findByOldUrl(string $oldUrl, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM redirects WHERE old_url = ? AND website_id = ? LIMIT 1'
        );
        $stmt->execute([$oldUrl, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $websiteId, string $oldUrl, string $newUrl, int $statusCode): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO redirects (website_id, old_url, new_url, status_code, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$websiteId, $oldUrl, $newUrl, $statusCode]);
        $id = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare('SELECT * FROM redirects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM redirects WHERE id = ?')->execute([$id]);
    }
}
