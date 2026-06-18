<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // e.g. "Summer 2026"
            $table->string('slug')->unique();  // e.g. "summer-2026"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Exactly one term should carry is_current = true at a time.
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('is_current');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
