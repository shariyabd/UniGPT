<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('chat_message_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('title');
            $table->longText('question')->nullable();
            $table->longText('answer');
            $table->string('category')->default('General');
            $table->json('tags')->nullable();
            $table->json('sources')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->text('notes')->nullable();
            $table->string('folder')->default('General');
            $table->boolean('starred')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'folder']);
            $table->index(['user_id', 'starred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_answers');
    }
};
