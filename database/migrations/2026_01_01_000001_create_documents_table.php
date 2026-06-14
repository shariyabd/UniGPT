<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()
                ->constrained()
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('category')->default('General');
            $table->string('file_type', 20)->nullable();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('pages')->nullable();
            $table->string('version')->default('1.0');
            $table->string('status')->default('pending');
            $table->string('visibility')->default('students'); // public, students, faculty, admins
            $table->json('tags')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility']);
            $table->index(['department_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
