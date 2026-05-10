<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('investment_plans')->cascadeOnDelete();
            $table->foreignId('duration_id')->constrained('durations')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->bigInteger('profit_cents')->default(0);
            // Set by admin profit override
            $table->bigInteger('adjusted_profit_cents')->nullable();
            $table->enum('status', [
                'pending',
                'active',
                'completed',
                'cancelled',
                'rejected',
            ])->default('pending');
            $table->enum('result', ['WIN', 'LOSS', 'DRAW'])->nullable();
            $table->timestamp('maturity_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Snapshot of terms version accepted at investment time
            $table->string('terms_version', 50);
            $table->boolean('created_by_admin')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['maturity_at', 'status']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
