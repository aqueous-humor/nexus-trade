<?php

namespace App\Http\Controllers\Wallet;

use App\Contracts\WalletServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\FeeCalculator;
use App\Services\RateLimiterService;
use App\Values\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    // Supported currencies and their networks
    private const CRYPTO_NETWORKS = [
        'BTC'  => ['BTC'],
        'ETH'  => ['ERC-20'],
        'USDT' => ['ERC-20', 'BEP-20', 'TRC-20'],
        'BNB'  => ['BEP-20'],
    ];

    private const FIAT_CURRENCIES = ['USD', 'EUR', 'GBP'];

    // Mock exchange rates (in production these come from an external API)
    private const EXCHANGE_RATES = [
        'BTC'  => 65000.00,
        'ETH'  => 3200.00,
        'USDT' => 1.00,
        'BNB'  => 580.00,
        'EUR'  => 1.08,
        'GBP'  => 1.27,
        'USD'  => 1.00,
    ];

    // Mock network fees in USD
    private const NETWORK_FEES = [
        'ERC-20' => 5.00,
        'BEP-20' => 0.50,
        'TRC-20' => 1.00,
        'BTC'    => 2.00,
    ];

    public function __construct(
        private readonly WalletServiceInterface $walletService,
        private readonly FeeCalculator $feeCalculator,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    /**
     * POST /api/v1/wallet/deposit
     * Initiate a deposit — returns wallet address and fee info.
     */
    public function initiate(Request $request): JsonResponse
    {
        // Rate limit: 5 deposits per minute
        $this->rateLimiter->checkDeposit($request->user()->id);

        $data = $request->validate([
            'currency' => ['required', 'string', 'in:USD,EUR,GBP,BTC,ETH,USDT,BNB'],
            'network'  => ['nullable', 'string', 'in:ERC-20,BEP-20,TRC-20,BTC'],
            'provider' => ['nullable', 'string', 'max:100'],
            'amount'   => ['required', 'numeric', 'min:0.01'],
        ]);

        $currency    = $data['currency'];
        $amountRaw   = (float) $data['amount'];
        $provider    = $data['provider'] ?? 'default';
        $isCrypto    = isset(self::CRYPTO_NETWORKS[$currency]);

        // Validate network for crypto
        if ($isCrypto) {
            $network = $data['network'] ?? array_key_first(self::CRYPTO_NETWORKS[$currency]);
            if (! in_array($network, self::CRYPTO_NETWORKS[$currency], true)) {
                return response()->json([
                    'message' => "Network '{$network}' is not supported for {$currency}.",
                    'code'    => 'INVALID_NETWORK',
                ], 422);
            }
        } else {
            $network = null;
        }

        // Convert to USD cents
        $rate        = self::EXCHANGE_RATES[$currency] ?? 1.0;
        $usdAmount   = $amountRaw * $rate;
        $grossCents  = (int) round($usdAmount * 100);

        // Compute provider fee
        $fees        = $this->feeCalculator->calculate($provider, 'deposit', $grossCents);
        $networkFee  = $network ? (self::NETWORK_FEES[$network] ?? 0) : 0;

        // Create pending transaction
        $transaction = Transaction::create([
            'user_id'          => $request->user()->id,
            'wallet_id'        => $request->user()->wallet->id,
            'type'             => 'deposit',
            'status'           => 'pending',
            'amount_cents'     => $grossCents,
            'fee_cents'        => $fees['fee_cents'],
            'net_amount_cents' => $fees['net_amount_cents'],
            'currency'         => $currency,
            'exchange_rate'    => $rate,
            'provider'         => $provider,
            'metadata'         => [
                'network'     => $network,
                'network_fee' => $networkFee,
                'raw_amount'  => $amountRaw,
            ],
        ]);

        return response()->json([
            'data' => [
                'transaction_id'  => $transaction->id,
                'wallet_address'  => $this->generateWalletAddress($currency, $network),
                'network'         => $network,
                'network_fee_usd' => $networkFee,
                'exchange_rate'   => $rate,
                'estimated_usd'   => number_format($usdAmount, 2),
                'provider_fee'    => number_format($fees['fee_cents'] / 100, 2),
                'net_usd'         => number_format($fees['net_amount_cents'] / 100, 2),
            ],
        ], 201);
    }

    /**
     * POST /api/v1/wallet/deposit/{transaction}/confirm
     * Confirm an on-chain deposit and credit the wallet.
     */
    public function confirm(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($transaction->status !== 'pending' || $transaction->type !== 'deposit') {
            return response()->json(['message' => 'Transaction cannot be confirmed.', 'code' => 'INVALID_STATE'], 422);
        }

        // Credit net amount to wallet
        $this->walletService->credit(
            $transaction->user_id,
            Money::fromCents($transaction->net_amount_cents),
            'deposit',
            [
                'fee_cents'     => $transaction->fee_cents,
                'currency'      => $transaction->currency,
                'exchange_rate' => $transaction->exchange_rate,
                'provider'      => $transaction->provider,
                'parent_id'     => $transaction->id,
            ]
        );

        $transaction->update(['status' => 'completed']);

        return response()->json(['data' => $transaction->fresh()]);
    }

    private function generateWalletAddress(string $currency, ?string $network): string
    {
        // In production this calls the blockchain node / custodial wallet API
        return match ($network ?? $currency) {
            'ERC-20', 'BEP-20' => '0x' . strtolower(bin2hex(random_bytes(20))),
            'TRC-20'           => 'T' . strtoupper(bin2hex(random_bytes(16))),
            'BTC'              => '1' . strtolower(bin2hex(random_bytes(16))),
            default            => strtolower(bin2hex(random_bytes(20))),
        };
    }
}
