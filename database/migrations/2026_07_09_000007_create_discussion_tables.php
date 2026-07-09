<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A section is the discussion "group" — membership is derived from
        // enrolment (students) and teaching (faculty). Posts are scoped to it.
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes(); // moderation removals are recoverable

            $table->index(['section_id', 'is_pinned', 'created_at']);
        });

        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'created_at']);
        });

        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('type')->default('like');
            $table->timestamps();

            $table->unique(['post_id', 'user_id', 'type']);
        });

        Schema::create('post_reports', function (Blueprint $table) {
            $table->id();
            // A report targets either a post or a comment (exactly one is set).
            $table->foreignId('post_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('post_comment_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('reporter_id')
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('reason');
            $table->string('status')->default('open'); // open | resolved
            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reports');
        Schema::dropIfExists('post_reactions');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('posts');
    }
};
