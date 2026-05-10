<?php

namespace App\Exceptions;

class RateLimitExceededException extends AppException
{
    public function __construct(
        public readonly int $retryAfter,
        string $message = 'Rate limit exceeded',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
