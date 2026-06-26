<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class LoginDTO
{
    public readonly string $email;
    public readonly string $password;

    public function __construct(array $data)
    {
        $errors = [];

        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email must be a valid email address.';
        }

        $password = $data['password'] ?? '';
        if (empty($password)) {
            $errors['password'][] = 'Password is required.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->email    = strtolower($email);
        $this->password = $password;
    }
}
