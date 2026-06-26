<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class MediaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(int $websiteId, array $filters = []): array
    {
        $sql    = 'SELECT m.*, u.name AS uploader_name
                   FROM media m
                   JOIN users u ON u.id = m.uploaded_by
                   WHERE m.website_id = ?';
        $params = [$websiteId];

        if (!empty($filters['search'])) {
            $sql      .= ' AND (m.file_name LIKE ? OR m.alt_text LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['type'])) {
            $sql      .= ' AND m.mime_type LIKE ?';
            $params[] = $filters['type'] . '%';
        }

        if (!empty($filters['uploaded_by'])) {
            $sql      .= ' AND m.uploaded_by = ?';
            $params[] = (int) $filters['uploaded_by'];
        }

        $sql .= ' ORDER BY m.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $websiteId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, u.name AS uploader_name
             FROM media m
             JOIN users u ON u.id = m.uploaded_by
             WHERE m.id = ? AND m.website_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, $websiteId]);
        return $stmt->fetch() ?: null;
    }

    public function create(
        int $websiteId,
        int $uploadedBy,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $size,
        ?int $width,
        ?int $height,
        ?string $altText,
        ?string $caption,
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO media (website_id, uploaded_by, file_name, file_path, mime_type, size, width, height, alt_text, caption, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $websiteId, $uploadedBy, $fileName, $filePath,
            $mimeType, $size, $width, $height, $altText, $caption,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateMeta(int $id, ?string $altText, ?string $caption): void
    {
        $stmt = $this->db->prepare(
            'UPDATE media SET alt_text = ?, caption = ? WHERE id = ?'
        );
        $stmt->execute([$altText, $caption, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM media WHERE id = ?');
        $stmt->execute([$id]);
    }
}
