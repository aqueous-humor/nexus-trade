<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            // Self-referencing: fee transactions link to their parent
            $table->foreignId('parent_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->enum('type', [
                'deposit',
                'withdrawal',
                'investment_debit',
                'profit',
                'fee',
                'refund',
                'cancellation',
            ]);
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'pending_review',
            ])->default('pending');
            $table->bigInteger('amount_cents');
            $table->bigInteger('fee_cents')->default(0);
            $table->bigInteger('net_amount_cents');
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->string('provider', 100)->nullable();
            $table->string('destination_address', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
