<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users may legitimately have no department (e.g. the System Administrator),
     * and the admin update flow treats department_id as nullable. The original
     * column was NOT NULL, which raised an integrity-constraint violation on
     * update. Make it nullable to match the validation contract.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onUpdate('cascade')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
