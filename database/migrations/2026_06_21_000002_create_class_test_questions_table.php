<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_test_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('type')->default('mcq'); // mcq, true_false
            $table->text('question_text');
            $table->json('options')->nullable(); // [{key, text}] for mcq; ignored for true_false
            $table->string('correct_answer'); // option key (mcq) or "true"/"false"
            $table->unsignedSmallInteger('marks')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('class_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_questions');
    }
};
