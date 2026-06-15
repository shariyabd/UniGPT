<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('title');
            $table->text('content')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
