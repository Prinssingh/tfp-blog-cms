<?php

declare(strict_types=1);

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Repositories\RoleRepository;
use stdClass;

class Policy
{
    private static ?array $permissionCache = null;
    private static ?int   $cachedRoleId    = null;

    public static function check(stdClass $auth, string $permission): void
    {
        if ($auth->role === 'super_admin') {
            return;
        }

        $permissions = self::loadPermissions($auth);

        if (!in_array($permission, $permissions, true)) {
            throw new ForbiddenException("You do not have permission: {$permission}");
        }
    }

    public static function requireRole(stdClass $auth, string ...$roles): void
    {
        if (!in_array($auth->role, $roles, true)) {
            throw new ForbiddenException('You do not have the required role for this action.');
        }
    }

    public static function requireSuperAdmin(stdClass $auth): void
    {
        if ($auth->role !== 'super_admin') {
            throw new ForbiddenException('Super admin access required.');
        }
    }

    public static function requireWebsiteAccess(stdClass $auth, int $websiteId): void
    {
        if ($auth->role === 'super_admin') {
            return;
        }

        if ((int) $auth->website_id !== $websiteId) {
            throw new ForbiddenException('You do not have access to this website.');
        }
    }

    private static function loadPermissions(stdClass $auth): array
    {
        $roleRepo = new RoleRepository();
        $role     = $roleRepo->findBySlug($auth->role);

        if ($role === null) {
            throw new UnauthorizedException('Role not found.');
        }

        if (self::$cachedRoleId !== $role['id']) {
            self::$permissionCache = $roleRepo->permissionsForRole((int) $role['id']);
            self::$cachedRoleId    = $role['id'];
        }

        return self::$permissionCache ?? [];
    }
}
