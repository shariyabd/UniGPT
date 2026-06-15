<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_materials', function (Blueprint $table) {
            // A material may carry a directly-uploaded file (faculty resource),
            // separate from the admin-curated knowledge-base `document_id`.
            $table->string('file_path')->nullable()->after('document_id');
            $table->string('original_filename')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('course_materials', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_filename', 'file_size']);
        });
    }
};
