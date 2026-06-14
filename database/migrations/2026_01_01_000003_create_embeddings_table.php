<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_chunk_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('document_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('model');
            $table->unsignedInteger('dimensions');
            $table->longText('vector'); // JSON-encoded float array
            $table->timestamps();

            $table->index(['model', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
