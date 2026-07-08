<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('class_test_attempts')
                ->onUpdate('cascade')->onDelete('cascade');
            // Optional question context (which question was on screen).
            $table->foreignId('question_id')->nullable()
                ->constrained('class_test_questions')
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('type');                       // fullscreen_exit, tab_blur, copy_attempt, focus_loss, answer, idle, resize, click, ...
            $table->string('severity')->default('info');  // info | warning | violation
            $table->timestamp('occurred_at');             // client-reported event time
            $table->unsignedInteger('duration_ms')->nullable(); // e.g. how long the tab was hidden / idle span
            $table->json('meta')->nullable();             // extra context (coords, key counts, question index, ...)
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();  // server-side receive time

            $table->index(['attempt_id', 'type']);
            $table->index(['attempt_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_events');
    }
};
