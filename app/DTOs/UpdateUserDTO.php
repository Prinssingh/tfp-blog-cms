<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateUserDTO
{
    public readonly ?string $name;
    public readonly ?int $roleId;
    public readonly ?int $websiteId;
    public readonly ?string $bio;
    public readonly ?string $status;
    public readonly ?string $password;

    public function __construct(array $data)
    {
        $errors = [];

        $name = isset($data['name']) ? trim($data['name']) : null;
        if ($name !== null && strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $password = isset($data['password']) ? $data['password'] : null;
        if ($password !== null && strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        $status = $data['status'] ?? null;
        if ($status !== null && !in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name      = $name ?: null;
        $this->roleId    = isset($data['role_id']) ? (int) $data['role_id'] : null;
        $this->websiteId = isset($data['website_id']) ? (int) $data['website_id'] : null;
        $this->bio       = isset($data['bio']) ? (trim($data['bio']) ?: null) : null;
        $this->status    = $status;
        $this->password  = $password ?: null;
    }
}
