<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('document_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('lecture'); // lecture, assignment, reading, slides, video
            $table->unsignedInteger('week')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('downloads')->default(0);
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamps();

            $table->index(['course_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
