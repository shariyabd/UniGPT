<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('class_test_attempts')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('question_id')
                ->constrained('class_test_questions')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('selected_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedSmallInteger('marks_awarded')->default(0);
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_answers');
    }
};
