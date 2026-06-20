<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_test_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('status')->default('in_progress'); // in_progress, submitted, disqualified, expired
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('total_marks')->default(0);
            $table->unsignedTinyInteger('violation_count')->default(0);
            $table->timestamps();

            // One attempt per student per test.
            $table->unique(['class_test_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_attempts');
    }
};
