<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_assessments', function (Blueprint $table) {
            $table->id();
            // Polymorphic: 'transaction' or 'investment'
            $table->string('assessable_type', 100);
            $table->unsignedBigInteger('assessable_id');
            // 0-100 risk score
            $table->unsignedTinyInteger('risk_score');
            // Array of triggered rule names
            $table->json('triggered_rules');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('review_decision', ['approved', 'rejected'])->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['assessable_type', 'assessable_id']);
            $table->index('risk_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_assessments');
    }
};
