<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('role'); // user, assistant
            $table->longText('content');
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('confidence_level')->nullable();
            $table->json('sources')->nullable();
            $table->json('follow_ups')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens')->default(0);
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
