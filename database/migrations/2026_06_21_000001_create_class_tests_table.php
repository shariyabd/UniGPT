<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('section_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable(); // rules / instructions shown before start
            $table->unsignedSmallInteger('duration_minutes')->default(15);
            $table->unsignedSmallInteger('total_marks')->default(0); // derived from questions
            $table->unsignedSmallInteger('pass_marks')->nullable();
            $table->string('status')->default('draft'); // draft, published, closed
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->boolean('shuffle_questions')->default(true);
            $table->unsignedTinyInteger('max_warnings')->default(1); // violations allowed before disqualification
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamps();

            $table->index(['section_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_tests');
    }
};
