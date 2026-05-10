<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters — dependencies must be seeded before dependents:
     *   1. Users (+ wallets + notification preferences)
     *   2. Brokers
     *   3. Accounts (depends on users + brokers)
     *   4. Plans
     *   5. Durations (depends on plans for pivot)
     *   6. Terms
     *   7. Investments (depends on users + accounts + plans + durations + terms)
     *   8. Transactions (depends on users + wallets)
     *   9. Signals
     *  10. Fee rules
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BrokerSeeder::class,
            AccountSeeder::class,
            PlanSeeder::class,
            DurationSeeder::class,
            TermsSeeder::class,
            InvestmentSeeder::class,
            TransactionSeeder::class,
            SignalSeeder::class,
            FeeRuleSeeder::class,
        ]);
    }
}
