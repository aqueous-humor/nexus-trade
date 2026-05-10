<?php

return [
    'demo_account_default_balance_cents' => (int) env('DEMO_ACCOUNT_DEFAULT_BALANCE_CENTS', 1000000),
    'withdrawal_daily_limit_cents'       => (int) env('WITHDRAWAL_DAILY_LIMIT_USD', 5000) * 100,
    'withdrawal_monthly_limit_cents'     => (int) env('WITHDRAWAL_MONTHLY_LIMIT_USD', 50000) * 100,
    'fraud' => [
        'high_frequency_threshold'       => (int) env('FRAUD_HIGH_FREQUENCY_THRESHOLD', 3),
        'high_frequency_window_minutes'  => (int) env('FRAUD_HIGH_FREQUENCY_WINDOW_MINUTES', 10),
        'large_transaction_cents'        => (int) env('FRAUD_LARGE_TRANSACTION_USD', 10000) * 100,
        'unusual_withdrawal_ratio'       => (float) env('FRAUD_UNUSUAL_WITHDRAWAL_RATIO', 0.80),
        'auto_review_score'              => (int) env('FRAUD_AUTO_REVIEW_SCORE', 80),
    ],
];
