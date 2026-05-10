<?php

namespace App\Contracts;

use App\DTOs\CreateInvestmentDTO;
use App\Models\Account;
use App\Models\Investment;
use App\Models\User;

interface InvestmentServiceInterface
{
    public function create(User $user, Account $account, CreateInvestmentDTO $dto): Investment;

    public function activate(Investment $investment): Investment;

    public function complete(Investment $investment, string $result): Investment;

    public function cancel(Investment $investment): Investment;

    public function reject(Investment $investment, string $reason): Investment;

    public function recover(Investment $investment): Investment;
}
