<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A pending placement (status `pending`) is assigned by the admin but not yet
 * confirmed by the student, so it has no enrollment time. Allow `enrolled_at`
 * to be null; it is stamped only when the student registers (status `enrolled`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->timestamp('enrolled_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->timestamp('enrolled_at')->useCurrent()->nullable(false)->change();
        });
    }
};
