<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('broker_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['demo', 'live']);
            $table->string('broker_account_id', 100)->nullable();
            $table->bigInteger('balance_cents')->default(0);
            $table->unsignedSmallInteger('leverage')->default(100);
            $table->enum('status', ['active', 'suspended', 'deactivated'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
