<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id') // the recipient
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('type')->default('system'); // grade, material, exam, announcement, system
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('link')->nullable(); // in-app destination (named route URL)
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
