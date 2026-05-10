<?php

namespace App\Jobs;

use App\Models\Investment;
use App\Services\InvestmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvestmentMaturity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 300, 1800];

    public function __construct(public readonly int $investmentId) {}

    public function handle(InvestmentService $service): void
    {
        $investment = Investment::find($this->investmentId);

        if (! $investment) {
            Log::warning("ProcessInvestmentMaturity: investment #{$this->investmentId} not found");
            return;
        }

        // Guard: only process active investments past maturity
        if ($investment->status !== 'active' || $investment->maturity_at->isFuture()) {
            return;
        }

        try {
            // Default result is WIN — admin can override via admin panel
            $service->complete($investment, 'WIN');
        } catch (\Throwable $e) {
            Log::error("ProcessInvestmentMaturity failed for #{$this->investmentId}: {$e->getMessage()}");
            throw $e;
        }
    }
}
