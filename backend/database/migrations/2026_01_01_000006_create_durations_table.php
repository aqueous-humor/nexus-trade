<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('durations', function (Blueprint $table) {
            $table->id();
            $table->enum('unit', ['hour', 'day', 'week', 'month']);
            // e.g. 4 for "4 hours"
            $table->unsignedSmallInteger('value');
            // e.g. "4 Hours"
            $table->string('label', 50);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['unit', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('durations');
    }
};
