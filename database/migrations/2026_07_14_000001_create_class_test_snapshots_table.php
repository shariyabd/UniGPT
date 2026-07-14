<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_test_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')
                ->constrained('class_test_attempts')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('trigger', 40);                   // violation | face_lost | phone_detected | multiple_faces | identity | periodic
            $table->unsignedInteger('sequence')->default(0); // capture index within the attempt
            $table->string('disk');                          // storage disk the frame lives on
            $table->string('path');                          // relative path on that disk
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index(['attempt_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_test_snapshots');
    }
};
