<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Policies\Policy;
use App\Repositories\AuditRepository;

class AuditController
{
    private AuditRepository $auditRepository;

    public function __construct()
    {
        $this->auditRepository = new AuditRepository();
    }

    public function index(Request $request): Response
    {
        $auth = $request->param('_auth');

        $filters = [
            'website_id'  => $request->query('website_id'),
            'user_id'     => $request->query('user_id'),
            'entity_type' => $request->query('entity_type'),
            'action'      => $request->query('action'),
            'limit'       => $request->query('limit') ?? 50,
        ];

        // Non-super-admins can only see their own website activity
        if ($auth->role !== 'super_admin') {
            Policy::requireWebsiteAccess($auth, (int) ($filters['website_id'] ?? $auth->website_id));
            $filters['website_id'] = $auth->website_id;
        }

        $logs = $this->auditRepository->getActivity($filters);

        $formatted = array_map(fn($log) => [
            'id'          => $log['id'],
            'action'      => $log['action'],
            'entity_type' => $log['entity_type'],
            'entity_id'   => $log['entity_id'],
            'new_values'  => $log['new_values'] ? json_decode($log['new_values'], true) : null,
            'old_values'  => $log['old_values'] ? json_decode($log['old_values'], true) : null,
            'ip_address'  => $log['ip_address'],
            'created_at'  => $log['created_at'],
            'actor'       => [
                'id'     => $log['user_id'],
                'name'   => $log['actor_name'],
                'email'  => $log['actor_email'],
                'avatar' => $log['actor_avatar'],
            ],
        ], $logs);

        return Response::success($formatted);
    }
}
