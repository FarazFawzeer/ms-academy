<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client change (24 Oct 2026): a session is used on every practice day of the player,
 * present OR absent (until the package is used up).
 *  - absences: one row per player per practice day missed (counted = it used a session)
 *  - practice_sessions.closed_at: set when the day has been settled (absences written),
 *    so it is never settled twice. Days already in the past are marked closed here,
 *    so deploying this never takes sessions away for old practices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->string('age_group', 10)->nullable();
            $t->date('attendance_date');
            $t->boolean('counted')->default(true);          // false = package was already used up
            $t->timestamp('created_at')->useCurrent();

            $t->unique(['player_id', 'attendance_date']);
            $t->index('attendance_date');
        });

        Schema::table('practice_sessions', function (Blueprint $t) {
            $t->timestamp('closed_at')->nullable()->after('note');
        });

        DB::table('practice_sessions')
            ->where('session_date', '<', now()->toDateString())
            ->update(['closed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('practice_sessions', function (Blueprint $t) {
            $t->dropColumn('closed_at');
        });

        Schema::dropIfExists('absences');
    }
};
