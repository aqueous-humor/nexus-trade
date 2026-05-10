<?php

namespace App\Exceptions;

use Carbon\Carbon;

class WithdrawalLimitExceededException extends AppException
{
    public function __construct(
        public readonly int $remainingCents,
        public readonly Carbon $resetsAt,
        string $message = 'Withdrawal limit exceeded',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
