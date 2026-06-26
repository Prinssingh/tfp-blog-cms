<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class RefreshTokenDTO
{
    public readonly string $refreshToken;

    public function __construct(array $data)
    {
        $token = trim($data['refresh_token'] ?? '');

        if (empty($token)) {
            throw new ValidationException(['refresh_token' => ['Refresh token is required.']]);
        }

        $this->refreshToken = $token;
    }
}
