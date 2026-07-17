<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visit-tracking log: one row per meaningful page view (landing page + each role's
 * dashboard and beyond). Captures who, from where (referrer), on what device, and
 * an IP-derived location — surfaced in Admin → User Activity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->string('path', 1024);
            $table->string('route_name')->nullable();
            $table->string('label')->nullable();
            $table->string('referrer', 1024)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 20)->nullable();   // desktop | mobile | tablet | bot
            $table->string('platform', 40)->nullable();       // Windows | macOS | Android | iOS | Linux
            $table->string('browser', 60)->nullable();        // Chrome | Safari | Firefox | Edge
            $table->string('country', 80)->nullable();
            $table->string('city', 120)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
            $table->index('route_name');
            $table->index('country');
            $table->index('device_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
