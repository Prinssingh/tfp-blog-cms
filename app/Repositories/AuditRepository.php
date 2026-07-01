<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getActivity(array $filters = []): array
    {
        $sql    = 'SELECT al.*, u.name AS actor_name, u.email AS actor_email, u.avatar AS actor_avatar
                   FROM audit_logs al
                   JOIN users u ON u.id = al.user_id
                   WHERE 1=1';
        $params = [];

        if (!empty($filters['website_id'])) {
            $sql      .= ' AND al.website_id = ?';
            $params[] = (int) $filters['website_id'];
        }
        if (!empty($filters['user_id'])) {
            $sql      .= ' AND al.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $sql      .= ' AND al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['action'])) {
            $sql      .= ' AND al.action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }

        $limit    = min((int) ($filters['limit'] ?? 50), 200);
        $sql     .= ' ORDER BY al.created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function log(
        int $userId,
        string $action,
        ?int $websiteId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs
                (website_id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $websiteId,
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ipAddress,
            $userAgent,
        ]);
    }
}
