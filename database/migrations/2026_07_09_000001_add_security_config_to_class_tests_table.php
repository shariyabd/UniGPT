<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_tests', function (Blueprint $table) {
            // Per-test selection of security layers: { layerKey: bool }. Null =
            // fall back to the config defaults (see config/exam_security.php).
            $table->json('security_config')->nullable()->after('max_warnings');
        });
    }

    public function down(): void
    {
        Schema::table('class_tests', function (Blueprint $table) {
            $table->dropColumn('security_config');
        });
    }
};
