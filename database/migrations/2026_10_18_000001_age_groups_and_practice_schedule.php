<?php

use App\Support\AgeGroup;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client change (Oct 2026):
 *  - no manual batches: the age group comes from the date of birth (U8 ... U18, Senior)
 *  - each player trains on Friday, Sunday or both (players.training_days)
 *  - a practice schedule the admin can move / cancel (practice_sessions)
 *  - attendances keep the age group of that day and flag "extra" (marked on a non-training day by an admin)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $t) {
            $t->id();
            $t->string('slot', 10);                       // fri | sun (keys of config academy.training_days)
            $t->date('planned_date');                     // the regular day this session belongs to
            $t->date('session_date');                     // the day it really happens (differs when moved)
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->boolean('is_cancelled')->default(false);
            $t->string('note', 150)->nullable();
            $t->timestamps();

            $t->unique(['slot', 'planned_date']);         // the auto-fill can never create a session twice
            $t->index('session_date');
        });

        Schema::table('players', function (Blueprint $t) {
            $t->string('training_days', 20)->default('fri,sun')->after('guardian_phone');
        });

        Schema::table('attendances', function (Blueprint $t) {
            $t->string('age_group', 10)->nullable()->after('player_id');
            $t->boolean('is_extra')->default(false)->after('method');
        });

        // age group on the day of each existing attendance
        DB::table('attendances')
            ->join('players', 'players.id', '=', 'attendances.player_id')
            ->select('attendances.id', 'attendances.attendance_date', 'players.dob')
            ->orderBy('attendances.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $r) {
                    $age = Carbon::parse($r->dob)->diffInYears(Carbon::parse($r->attendance_date));
                    DB::table('attendances')->where('id', $r->id)->update(['age_group' => AgeGroup::forAge((int) $age)]);
                }
            });

        // manual batches are gone
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropForeign(['batch_id']);
            $t->dropIndex(['attendance_date', 'batch_id']);
            $t->dropColumn('batch_id');
        });

        Schema::table('players', function (Blueprint $t) {
            $t->dropForeign(['batch_id']);
            $t->dropColumn('batch_id');
        });

        Schema::dropIfExists('batches');

        Schema::table('attendances', function (Blueprint $t) {
            $t->index(['attendance_date', 'age_group']);
        });
    }

    public function down(): void
    {
        Schema::create('batches', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('schedule')->nullable();
            $t->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::table('attendances', function (Blueprint $t) {
            $t->dropIndex(['attendance_date', 'age_group']);
            $t->dropColumn(['age_group', 'is_extra']);
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->index(['attendance_date', 'batch_id']);
        });

        Schema::table('players', function (Blueprint $t) {
            $t->dropColumn('training_days');
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('practice_sessions');
    }
};
