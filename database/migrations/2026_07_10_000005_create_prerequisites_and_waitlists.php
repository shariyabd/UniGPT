<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which courses must be COMPLETED before a student may register for
        // course_id. Managed by admins on the course catalog.
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_id']);
        });

        // FIFO waitlist per section: when an admin assigns students to a full
        // section they queue here; a drop auto-promotes the head of the queue
        // to a pending placement.
        Schema::create('section_waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['section_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_waitlists');
        Schema::dropIfExists('course_prerequisites');
    }
};
