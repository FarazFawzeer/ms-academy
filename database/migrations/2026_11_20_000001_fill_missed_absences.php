<?php

use App\Models\Player;
use App\Support\PlayerSessions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client (20 Nov 2026): players added with an old joining date did not get their missed practices as
 * absences (a day is settled once, for the players who existed then). This fills them in and works the
 * sessions out again in date order. On the live host the same step runs by itself on the first page
 * load after the upload (App\Http\Middleware\SettlePracticeDays::fillOnce), no SQL needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Player::where('is_active', true)->get() as $p) {
            DB::transaction(fn () => PlayerSessions::fillPastAbsences($p));
        }
    }

    public function down(): void
    {
        // data step only
    }
};
