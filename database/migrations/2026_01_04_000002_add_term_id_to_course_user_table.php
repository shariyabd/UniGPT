<?php

use App\Models\Term;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->foreignId('term_id')->nullable()->after('user_id')
                ->constrained('terms')
                ->onUpdate('cascade')->onDelete('set null');
            $table->index(['term_id', 'status']);
        });

        // Backfill existing enrollments onto a current term so current/past logic
        // has an anchor on already-migrated databases. Fresh installs have no rows
        // here yet; the seeder assigns the proper term when enrolling.
        if (DB::table('course_user')->whereNull('term_id')->exists()) {
            $term = Term::query()->where('is_current', true)->first()
                ?? Term::create([
                    'name' => 'Current Term',
                    'slug' => 'current-term',
                    'is_current' => true,
                ]);

            DB::table('course_user')->whereNull('term_id')->update(['term_id' => $term->id]);
        }
    }

    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->dropIndex(['term_id', 'status']);
            $table->dropColumn('term_id');
        });
    }
};
