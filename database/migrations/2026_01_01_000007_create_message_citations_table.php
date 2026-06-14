<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('document_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('document_chunk_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->unsignedTinyInteger('relevance')->nullable();
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_citations');
    }
};
