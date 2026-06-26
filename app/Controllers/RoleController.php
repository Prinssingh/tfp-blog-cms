<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\RoleRepository;
use App\Services\RoleService;

class RoleController
{
    private RoleService $roleService;

    public function __construct()
    {
        $this->roleService = new RoleService(new RoleRepository());
    }

    public function index(Request $request): Response
    {
        return Response::success($this->roleService->allRoles());
    }

    public function permissions(Request $request): Response
    {
        return Response::success($this->roleService->allPermissions());
    }

    public function show(Request $request): Response
    {
        $role = $this->roleService->roleWithPermissions((int) $request->param('id'));
        return Response::success($role);
    }

    public function syncPermissions(Request $request): Response
    {
        $permissionIds = $request->body('permission_ids', []);
        $role          = $this->roleService->syncPermissions(
            (int) $request->param('id'),
            $permissionIds,
        );

        return Response::success($role, 'Permissions updated.');
    }
}
