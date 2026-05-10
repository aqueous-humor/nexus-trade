<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->enum('platform_type', ['MT4', 'MT5']);
            // Encrypted at rest via model casting
            $table->json('connection_credentials');
            $table->unsignedSmallInteger('default_leverage')->default(100);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brokers');
    }
};
