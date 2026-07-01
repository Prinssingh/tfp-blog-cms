<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RefreshTokenDTO;
use App\Exceptions\UnauthorizedException;
use App\Helpers\JwtHelper;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private readonly UserRepository  $userRepository,
        private readonly AuditRepository $auditRepository,
    ) {}

    public function login(LoginDTO $dto, string $ip, string $userAgent): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || !password_verify($dto->password, $user['password'])) {
            $this->auditRepository->log(
                userId: $user['id'] ?? 0,
                action: 'auth.login.failed',
                ipAddress: $ip,
                userAgent: $userAgent,
            );
            throw new UnauthorizedException('Invalid email or password.');
        }

        $payload = $this->buildTokenPayload($user);
        $ttl     = (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800);

        $accessToken  = JwtHelper::accessToken($payload);
        $refreshToken = JwtHelper::refreshToken($payload);

        $this->userRepository->storeRefreshToken($user['id'], $refreshToken, $ttl);
        $this->userRepository->updateLastLogin($user['id']);

        $this->auditRepository->log(
            userId: $user['id'],
            action: 'auth.login',
            websiteId: $user['website_id'] ?? null,
            ipAddress: $ip,
            userAgent: $userAgent,
        );

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => (int) ($_ENV['JWT_ACCESS_TTL'] ?? 3600),
            'user'          => $this->formatUser($user),
        ];
    }

    public function refresh(RefreshTokenDTO $dto): array
    {
        $stored = $this->userRepository->findRefreshToken($dto->refreshToken);

        if ($stored === null) {
            throw new UnauthorizedException('Invalid or expired refresh token.');
        }

        try {
            $decoded = JwtHelper::decode($dto->refreshToken);
        } catch (\Throwable) {
            $this->userRepository->deleteRefreshToken($dto->refreshToken);
            throw new UnauthorizedException('Invalid or expired refresh token.');
        }

        $user = $this->userRepository->findById((int) $decoded->sub);

        if ($user === null) {
            throw new UnauthorizedException('User not found.');
        }

        $this->userRepository->deleteRefreshToken($dto->refreshToken);

        $payload      = $this->buildTokenPayload($user);
        $ttl          = (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800);
        $accessToken  = JwtHelper::accessToken($payload);
        $refreshToken = JwtHelper::refreshToken($payload);

        $this->userRepository->storeRefreshToken($user['id'], $refreshToken, $ttl);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => (int) ($_ENV['JWT_ACCESS_TTL'] ?? 3600),
        ];
    }

    public function logout(string $refreshToken): void
    {
        $this->userRepository->deleteRefreshToken($refreshToken);
    }

    public function me(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new UnauthorizedException('User not found.');
        }

        return $this->formatUser($user);
    }

    private function buildTokenPayload(array $user): array
    {
        return [
            'sub'        => $user['id'],
            'email'      => $user['email'],
            'role'       => $user['role_slug'],
            'website_id' => $user['website_id'],
        ];
    }

    private function formatUser(array $user): array
    {
        $roleRepo    = new \App\Repositories\RoleRepository();
        $role        = $roleRepo->findBySlug($user['role_slug']);
        $permissions = ($user['role_slug'] === 'super_admin' || $role === null)
            ? ['*']
            : $roleRepo->permissionsForRole((int) $role['id']);

        return [
            'id'          => $user['id'],
            'name'        => $user['name'],
            'slug'        => $user['slug'],
            'email'       => $user['email'],
            'avatar'      => $user['avatar'],
            'bio'         => $user['bio'],
            'role'        => $user['role_slug'],
            'role_name'   => $user['role_name'],
            'website_id'  => $user['website_id'],
            'permissions' => $permissions,
        ];
    }
}
