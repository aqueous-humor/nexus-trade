<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signal_id')->constrained()->cascadeOnDelete();
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();

            // Index for finding active subscription per account
            $table->index(['account_id', 'unsubscribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_subscriptions');
    }
};
