<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class AppException extends RuntimeException
{
    public function __construct(
        string $message = 'An error occurred.',
        private readonly int $statusCode = 500,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
