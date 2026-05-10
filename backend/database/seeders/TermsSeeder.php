<?php

namespace Database\Seeders;

use App\Models\TermsVersion;
use Illuminate\Database\Seeder;

class TermsSeeder extends Seeder
{
    public function run(): void
    {
        TermsVersion::firstOrCreate(
            ['version' => 'v1.0'],
            [
                'content'      => 'Standard investment terms and conditions. By investing through NexusTrade, you acknowledge and accept the risks associated with forex and investment activities. All investments are subject to market risk. Past performance does not guarantee future results. NexusTrade is not responsible for any losses incurred through the use of our platform. Please read all terms carefully before proceeding.',
                'effective_at' => now()->subDay(),
            ]
        );
    }
}
