<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-chunk embedding vectors of each assignment submission's text
        // (written content + extracted file text), kept so new submissions can
        // be compared against classmates' without re-embedding them.
        Schema::create('submission_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('excerpt');
            $table->longText('vector'); // JSON array of floats (same convention as embeddings.vector)
            $table->string('model');
            $table->timestamps();

            $table->index(['assignment_id', 'model']);
            $table->index('assignment_submission_id');
        });

        // Flagged high-similarity pairs within one assignment. Stored in both
        // directions so each submission's flags are a single indexed lookup.
        Schema::create('submission_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->foreignId('matched_submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->float('score');    // best chunk-pair cosine similarity (0-1)
            $table->float('coverage'); // fraction of this submission's chunks with a flagged match
            $table->json('matched_chunks')->nullable(); // top excerpt pairs for review
            $table->string('model');
            $table->timestamps();

            $table->unique(['submission_id', 'matched_submission_id'], 'submission_similarities_pair_unique');
            $table->index('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_similarities');
        Schema::dropIfExists('submission_embeddings');
    }
};
