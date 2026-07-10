<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('title');
            $table->string('topic');
            $table->string('difficulty', 20)->default('medium');
            // Full question set including correct answers and explanations —
            // only ever sent to the client stripped (before submit) or as
            // grading results (after submit).
            $table->json('questions');
            $table->timestamps();

            $table->index(['user_id', 'course_id']);
        });

        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_quiz_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            // question id → submitted answer, as graded server-side.
            $table->json('answers');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total');
            $table->timestamp('completed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('practice_quizzes');
    }
};
