<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id') // the student
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('present'); // present, absent, late, excused
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamps();

            // One attendance record per student per course per day.
            $table->unique(['course_id', 'user_id', 'date']);
            $table->index(['course_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
