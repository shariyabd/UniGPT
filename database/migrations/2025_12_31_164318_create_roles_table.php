<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('level')->default(1);
            $table->timestamps();

            // Indexes
            $table->index(['slug', 'is_active']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
