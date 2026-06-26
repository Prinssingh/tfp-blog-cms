<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

class UpdateWebsiteDTO
{
    public readonly ?string $name;
    public readonly ?string $domain;
    public readonly ?string $logo;
    public readonly ?string $favicon;
    public readonly ?string $timezone;
    public readonly ?string $language;
    public readonly ?string $status;

    public function __construct(array $data)
    {
        $errors = [];

        $name = isset($data['name']) ? trim($data['name']) : null;
        if ($name !== null && strlen($name) > 255) {
            $errors['name'][] = 'Name must not exceed 255 characters.';
        }

        $status = $data['status'] ?? null;
        if ($status !== null && !in_array($status, ['active', 'inactive'], true)) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->name     = $name ?: null;
        $this->domain   = isset($data['domain']) ? strtolower(trim($data['domain'])) : null;
        $this->logo     = isset($data['logo']) ? (trim($data['logo']) ?: null) : null;
        $this->favicon  = isset($data['favicon']) ? (trim($data['favicon']) ?: null) : null;
        $this->timezone = $data['timezone'] ?? null;
        $this->language = $data['language'] ?? null;
        $this->status   = $status;
    }
}
