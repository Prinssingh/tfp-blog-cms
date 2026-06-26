<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message, 404);
    }
}
