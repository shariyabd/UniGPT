<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Where this row came from: 'library' (admin knowledge base, the
            // default for every pre-existing row), 'note' (shadow of a user's
            // personal note) or 'material' (shadow of a course material file).
            // Shadow rows exist only so notes/materials flow through the same
            // chunk → embed → retrieve pipeline; a global scope on the Document
            // model hides them from every library/admin surface.
            $table->string('source_type', 20)->default('library')->after('id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            // Owner of a personal shadow document (set for notes); personal
            // retrieval is scoped to this user.
            $table->foreignId('owner_id')->nullable()->after('source_id')
                ->constrained('users')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index(['source_type', 'source_id']);

            // Shadow documents for notes have no backing file.
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
