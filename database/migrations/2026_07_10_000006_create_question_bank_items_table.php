<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-course bank of reusable test questions, shared by every faculty
        // member teaching the course. Mirrors class_test_questions' shape so
        // items copy cleanly in both directions.
        Schema::create('question_bank_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('mcq'); // mcq, true_false
            $table->text('question_text');
            $table->json('options')->nullable(); // [{key, text}] for mcq
            $table->string('correct_answer');
            $table->unsignedSmallInteger('marks')->default(1);
            $table->string('topic')->nullable();
            $table->string('difficulty', 20)->default('medium');
            $table->timestamps();

            $table->index(['course_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
    }
};
