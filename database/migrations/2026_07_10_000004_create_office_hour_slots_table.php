<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_hour_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')
                ->constrained('users')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('location')->nullable(); // room / meeting link
            $table->string('note')->nullable();
            // Single-capacity slot: open while null, booked when set.
            $table->foreignId('booked_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();

            $table->index(['faculty_id', 'starts_at']);
            $table->index('booked_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hour_slots');
    }
};
