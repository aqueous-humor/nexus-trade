<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit logs are append-only — no updated_at column
        // The application DB user must NOT have UPDATE/DELETE on this table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation_type', 100);
            $table->enum('actor_type', ['user', 'admin', 'system']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            // SHA-256 of request payload for tamper detection
            $table->string('payload_hash', 64)->nullable();
            // 'success' or HTTP error code string
            $table->string('outcome', 50);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index('operation_type');
            $table->index('created_at');
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
