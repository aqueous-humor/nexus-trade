<?php

namespace App\Exceptions;

class InvalidStateTransitionException extends AppException
{
    public function __construct(
        public readonly string $fromState,
        public readonly string $toState,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === '') {
            $message = "Invalid state transition from '{$fromState}' to '{$toState}'";
        }

        parent::__construct($message, $code, $previous);
    }
}
