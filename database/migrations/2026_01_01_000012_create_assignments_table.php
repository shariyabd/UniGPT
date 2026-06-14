<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('homework'); // homework, project, quiz, exam
            $table->unsignedInteger('total_points')->default(100);
            $table->timestamp('due_at')->nullable();
            $table->json('rubric')->nullable();
            $table->string('status')->default('published'); // draft, published
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
