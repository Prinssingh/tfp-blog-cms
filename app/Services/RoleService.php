<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\RoleRepository;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
    ) {}

    public function allRoles(): array
    {
        return $this->roleRepository->all();
    }

    public function allPermissions(): array
    {
        return $this->roleRepository->allPermissions();
    }

    public function roleWithPermissions(int $roleId): array
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new NotFoundException('Role not found.');
        }

        $role['permissions'] = $this->roleRepository->permissionsForRole($roleId);

        return $role;
    }

    public function syncPermissions(int $roleId, array $permissionIds): array
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new NotFoundException('Role not found.');
        }

        $this->roleRepository->syncPermissions($roleId, $permissionIds);

        $role['permissions'] = $this->roleRepository->permissionsForRole($roleId);

        return $role;
    }
}
