<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 100);
            $table->enum('transaction_type', ['deposit', 'withdrawal']);
            $table->enum('fee_type', ['fixed', 'percentage']);
            $table->decimal('fee_value', 10, 4);
            $table->timestamps();

            $table->unique(['provider', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};
