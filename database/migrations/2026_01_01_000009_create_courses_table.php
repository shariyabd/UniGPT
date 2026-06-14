<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('faculty_id')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->unsignedInteger('semester')->nullable();
            $table->unsignedInteger('credits')->default(3);
            $table->json('schedule')->nullable();
            $table->unsignedInteger('max_enrollment')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department_id', 'semester']);
            $table->index('faculty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
