<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class CreateUserDTO
{
    public readonly ?int $websiteId;
    public readonly int $roleId;
    public readonly string $name;
    public readonly string $slug;
    public readonly string $email;
    public readonly string $password;
    public readonly ?string $bio;
    public readonly string $status;

    public function __construct(array $data)
    {
        $errors = [];

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'][] = 'Name is required.';
        } elseif (strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email must be a valid email address.';
        }

        $password = $data['password'] ?? '';
        if (empty($password)) {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        $roleId = (int) ($data['role_id'] ?? 0);
        if ($roleId <= 0) {
            $errors['role_id'][] = 'Role is required.';
        }

        $status = $data['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $slug = $data['slug'] ?? '';
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');
        }

        $this->websiteId = isset($data['website_id']) ? (int) $data['website_id'] : null;
        $this->roleId    = $roleId;
        $this->name      = $name;
        $this->slug      = $slug;
        $this->email     = strtolower($email);
        $this->password  = $password;
        $this->bio       = trim($data['bio'] ?? '') ?: null;
        $this->status    = $status;
    }
}
