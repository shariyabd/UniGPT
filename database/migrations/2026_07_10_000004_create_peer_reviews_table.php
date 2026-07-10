<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anonymous peer review: students who submitted review classmates'
        // submissions. reviewer_id is never exposed to the reviewee; a NULL
        // rating means the task is assigned but not yet completed.
        Schema::create('peer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5, null = pending
            $table->text('comments')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'reviewer_id']);
            $table->index(['assignment_id', 'reviewer_id']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('peer_review_enabled')->default(false)->after('rubric');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('peer_review_enabled');
        });
        Schema::dropIfExists('peer_reviews');
    }
};
