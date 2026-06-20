<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot linking documents to the departments they target. A document with
     * no rows here is considered visible to *all* departments.
     */
    public function up(): void
    {
        Schema::create('document_department', function (Blueprint $table) {
            $table->foreignId('document_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('department_id')
                ->constrained()
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['document_id', 'department_id']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_department');
    }
};
