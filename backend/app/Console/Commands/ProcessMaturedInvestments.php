<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInvestmentMaturity;
use App\Models\Investment;
use Illuminate\Console\Command;

class ProcessMaturedInvestments extends Command
{
    protected $signature   = 'investments:process-maturity';
    protected $description = 'Dispatch maturity jobs for all active investments past their maturity date';

    public function handle(): int
    {
        $count = 0;

        Investment::where('status', 'active')
            ->where('maturity_at', '<=', now())
            ->select('id')
            ->chunkById(100, function ($investments) use (&$count): void {
                foreach ($investments as $investment) {
                    ProcessInvestmentMaturity::dispatch($investment->id);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} maturity job(s).");

        return self::SUCCESS;
    }
}
