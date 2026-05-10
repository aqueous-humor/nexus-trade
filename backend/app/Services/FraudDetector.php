<?php

namespace App\Services;

use App\Contracts\FraudDetectorInterface;
use App\DTOs\RiskAssessment;
use App\Fraud\FraudContext;
use App\Fraud\FraudRuleInterface;
use App\Fraud\Rules\HighFrequencyDepositRule;
use App\Fraud\Rules\LargeTransactionRule;
use App\Fraud\Rules\NewAccountLargeDepositRule;
use App\Fraud\Rules\RapidSuccessionWithdrawalRule;
use App\Fraud\Rules\UnusualWithdrawalRule;
use App\Models\FraudAssessment;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FraudDetector implements FraudDetectorInterface
{
    /** @var FraudRuleInterface[] */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new HighFrequencyDepositRule(),
            new LargeTransactionRule(),
            new UnusualWithdrawalRule(),
            new NewAccountLargeDepositRule(),
            new RapidSuccessionWithdrawalRule(),
        ];
    }

    public function scoreTransaction(Transaction $transaction): RiskAssessment
    {
        $context = FraudContext::fromTransaction($transaction);
        return $this->assess($context, 'transaction', $transaction->id);
    }

    public function scoreInvestment(Investment $investment): RiskAssessment
    {
        $context = FraudContext::fromInvestment($investment);
        return $this->assess($context, 'investment', $investment->id);
    }

    private function assess(FraudContext $context, string $type, int $entityId): RiskAssessment
    {
        $results = [];

        foreach ($this->rules as $rule) {
            try {
                $result = $rule->evaluate($context);
                if ($result !== null) {
                    $results[] = $result;
                }
            } catch (\Throwable $e) {
                Log::warning("FraudRule {$rule->name()} failed: {$e->getMessage()}");
            }
        }

        $score         = min(100, array_sum(array_map(fn ($r) => $r->scoreContribution, $results)));
        $triggeredRules = array_map(fn ($r) => $r->ruleName, $results);

        // Persist assessment
        FraudAssessment::create([
            'assessable_type' => $type,
            'assessable_id'   => $entityId,
            'risk_score'      => $score,
            'triggered_rules' => $triggeredRules,
        ]);

        return new RiskAssessment($score, $triggeredRules);
    }
}
