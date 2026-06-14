<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->longText('content');
            $table->unsignedInteger('page')->nullable();
            $table->string('section')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
