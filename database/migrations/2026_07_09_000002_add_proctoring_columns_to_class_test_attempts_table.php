<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_test_attempts', function (Blueprint $table) {
            // Browser fingerprint captured at start (raw components + stable hash).
            $table->json('fingerprint')->nullable()->after('violation_count');
            $table->string('fingerprint_hash', 64)->nullable()->after('fingerprint');
            // Session/request identity for the watermark + evidence trail.
            $table->string('session_id', 64)->nullable()->after('fingerprint_hash');
            $table->string('ip_address', 45)->nullable()->after('session_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            // Risk analysis (computed on submit): 0–100 score + contributing factors.
            $table->unsignedTinyInteger('risk_score')->nullable()->after('user_agent');
            $table->json('risk_factors')->nullable()->after('risk_score');
        });
    }

    public function down(): void
    {
        Schema::table('class_test_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'fingerprint',
                'fingerprint_hash',
                'session_id',
                'ip_address',
                'user_agent',
                'risk_score',
                'risk_factors',
            ]);
        });
    }
};
