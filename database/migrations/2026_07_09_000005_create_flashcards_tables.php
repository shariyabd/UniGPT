<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashcard_decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            // Optional link to a course the deck relates to (nullable — decks can
            // be general study material too).
            $table->foreignId('course_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source')->default('manual'); // manual | ai
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')
                ->constrained('flashcard_decks')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->text('front');
            $table->text('back');
            $table->unsignedInteger('position')->default(0);

            // Spaced-repetition state (SM-2). Decks are single-owner, so review
            // scheduling lives on the card itself.
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['deck_id', 'position']);
            $table->index(['deck_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
        Schema::dropIfExists('flashcard_decks');
    }
};
