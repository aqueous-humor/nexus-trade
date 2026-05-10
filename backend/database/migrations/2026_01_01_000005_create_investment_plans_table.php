<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('min_amount_cents');
            $table->unsignedBigInteger('max_amount_cents');
            // e.g. 12.5000 = 12.5%
            $table->decimal('roi_percentage', 8, 4);
            $table->decimal('profit_min_pct', 8, 4);
            $table->decimal('profit_max_pct', 8, 4);
            $table->boolean('is_trending')->default(false);
            $table->string('trending_image_url', 500)->nullable();
            $table->string('trending_title', 255)->nullable();
            $table->text('trending_description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_trending', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
