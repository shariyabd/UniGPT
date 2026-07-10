<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonymous mid-semester course feedback, one response per student per
        // section. user_id exists ONLY to enforce that uniqueness and allow
        // edits while the window is open — it is never exposed to faculty or
        // admin surfaces.
        Schema::create('course_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'user_id']);
        });

        // Faculty open/close the collection window per section.
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('feedback_open')->default(false)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('feedback_open');
        });
        Schema::dropIfExists('course_feedback');
    }
};
