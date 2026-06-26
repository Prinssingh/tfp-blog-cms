<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateUserDTO;
use App\DTOs\UpdateUserDTO;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository  $userRepository,
        private readonly AuditRepository $auditRepository,
    ) {}

    public function all(array $filters = []): array
    {
        $users = $this->userRepository->all($filters);
        return array_map([$this, 'formatUser'], $users);
    }

    public function findById(int $id): array
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        return $this->formatUser($user);
    }

    public function create(CreateUserDTO $dto, int $actorId): array
    {
        if ($this->userRepository->emailExists($dto->email)) {
            throw new AppException('A user with this email already exists.', 409);
        }

        $id   = $this->userRepository->create($dto);
        $user = $this->userRepository->findById($id);

        $this->auditRepository->log(
            userId: $actorId,
            action: 'user.created',
            websiteId: $dto->websiteId,
            entityType: 'user',
            entityId: $id,
            newValues: ['email' => $dto->email, 'role_id' => $dto->roleId],
        );

        return $this->formatUser($user);
    }

    public function update(int $id, UpdateUserDTO $dto, int $actorId): array
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        if ($dto->websiteId !== null && $this->userRepository->emailExists($dto->websiteId !== null ? $user['email'] : '', $id)) {
            // email unchanged, skip check
        }

        $this->userRepository->update($id, $dto);

        $this->auditRepository->log(
            userId: $actorId,
            action: 'user.updated',
            entityType: 'user',
            entityId: $id,
        );

        return $this->formatUser($this->userRepository->findById($id));
    }

    public function delete(int $id, int $actorId): void
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        if ($id === $actorId) {
            throw new AppException('You cannot delete your own account.', 422);
        }

        $this->userRepository->deleteAllRefreshTokens($id);
        $this->userRepository->delete($id);

        $this->auditRepository->log(
            userId: $actorId,
            action: 'user.deleted',
            entityType: 'user',
            entityId: $id,
        );
    }

    private function formatUser(array $user): array
    {
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
            'status'      => $user['status'],
            'last_login_at' => $user['last_login_at'],
            'created_at'  => $user['created_at'],
        ];
    }
}
