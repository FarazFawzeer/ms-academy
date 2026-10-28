<?php

use App\Models\Player;
use App\Support\PlayerSessions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client report (28 Oct 2026): a Sunday-only player showed as absent on a Friday.
 * Cause: every existing player started as Friday + Sunday, absences were written, then staff
 * changed the player to one day. This one-time clean-up removes absences on days that are not
 * the player's training days (or before they joined) and gives those sessions back.
 * From now on the same clean-up runs whenever a player's training days or joining date change.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('absences')->distinct()->pluck('player_id');

        foreach (Player::whereIn('id', $ids)->get() as $player) {
            DB::transaction(fn () => PlayerSessions::removeWrongDayAbsences($player));
        }
    }

    public function down(): void
    {
        // data clean-up only: nothing to undo
    }
};
