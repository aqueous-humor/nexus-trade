<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('terms_version', 50);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45);

            $table->unique(['user_id', 'terms_version']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
    }
};
