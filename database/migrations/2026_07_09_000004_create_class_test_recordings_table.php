<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('class_test_attempts')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('kind');                          // webcam | screen
            $table->unsignedInteger('sequence')->default(0); // chunk index within the attempt+kind
            $table->string('disk');                          // storage disk the chunk lives on
            $table->string('path');                          // relative path on that disk
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['attempt_id', 'kind', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_recordings');
    }
};
