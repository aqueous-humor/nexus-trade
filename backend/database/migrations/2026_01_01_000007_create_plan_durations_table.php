<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('investment_plans')->cascadeOnDelete();
            $table->foreignId('duration_id')->constrained('durations')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['plan_id', 'duration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_durations');
    }
};
