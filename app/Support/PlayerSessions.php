<?php

namespace App\Support;

use App\Models\Player;
use App\Models\PracticeSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Session book-keeping that several screens share (Add / Edit player, manual marks on the profile,
 * changing training days). Every change keeps players.sessions_balance in step with the records:
 *   present  = an attendances row, always uses 1 session
 *   absent   = an absences row, uses 1 session only while the player still had sessions (counted = 1)
 * Call these inside a DB transaction.
 */
class PlayerSessions
{
    /**
     * Set one day for a player: 'present', 'absent' or 'clear'.
     * Whatever was recorded for that day is undone first (its session is given back), then the new
     * state is written. Returns the new balance.
     */
    public static function setDay(Player $player, string $date, string $status, ?int $userId = null): int
    {
        $date = Carbon::parse($date)->toDateString();

        // ---- undo what is there now ----
        $att = DB::table('attendances')->where('player_id', $player->id)->where('attendance_date', $date)->first(['id']);
        if ($att) {
            DB::table('attendances')->where('id', $att->id)->delete();
            DB::table('players')->where('id', $player->id)->increment('sessions_balance');
        }
        $abs = DB::table('absences')->where('player_id', $player->id)->where('attendance_date', $date)->first(['id', 'counted']);
        if ($abs) {
            DB::table('absences')->where('id', $abs->id)->delete();
            if ($abs->counted) {
                DB::table('players')->where('id', $player->id)->increment('sessions_balance');
            }
        }

        // ---- write the new state ----
        $session = PracticeSession::where('session_date', $date)->where('is_cancelled', false)->first();
        $group   = AgeGroup::forDob($player->dob, $date);

        if ($status === 'present') {
            $start = $session?->start_time ? substr($session->start_time, 0, 5) : '12:00';
            DB::table('attendances')->insert([
                'player_id'       => $player->id,
                'age_group'       => $group,
                'attendance_date' => $date,
                'method'          => 'manual',
                // a practice day that is not the player's own day = extra (make-up); days before the system had no practice record
                'is_extra'        => $session && !$player->trainsOn($session->slot),
                'marked_by'       => $userId,
                'created_at'      => $date . ' ' . $start . ':00',
            ]);
            DB::table('players')->where('id', $player->id)->decrement('sessions_balance');
        } elseif ($status === 'absent') {
            $counted = self::balance($player) > 0;
            DB::table('absences')->insert([
                'player_id'       => $player->id,
                'age_group'       => $group,
                'attendance_date' => $date,
                'counted'         => $counted,
                'created_at'      => now(),
            ]);
            if ($counted) {
                DB::table('players')->where('id', $player->id)->decrement('sessions_balance');
            }
        }

        return self::refreshReminder($player);
    }

    /**
     * Sessions the player already did before being added (or found later), paid or not.
     * Each typed date becomes an attendance row (a date already marked present is not counted twice);
     * the rest are taken off without a date. Returns the new balance.
     */
    public static function addEarlierSessions(Player $player, int $used, array $dates, ?int $userId = null): int
    {
        $dates = collect($dates)->filter()->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->sort()->values()->take($used);
        $undated = $used - $dates->count();

        foreach ($dates as $date) {
            $already = DB::table('attendances')->where('player_id', $player->id)->where('attendance_date', $date)->exists();
            if (!$already) {
                self::setDay($player, $date, 'present', $userId);
            }
        }

        if ($undated > 0) {
            DB::table('players')->where('id', $player->id)->decrement('sessions_balance', $undated);
        }

        // added with most of the package already used: remind as soon as a week or less is left
        return self::refreshReminder($player, true);
    }

    /**
     * Absences on days that are not (any more) the player's training days, or before their joining
     * date, are removed and their sessions given back - e.g. a Sunday-only player must never be
     * "absent" on a Friday.
     * Returns how many absences were removed.
     */
    public static function removeWrongDayAbsences(Player $player): int
    {
        $rows = DB::table('absences as a')
            ->join('practice_sessions as s', function ($j) {
                $j->on('s.session_date', '=', 'a.attendance_date')->where('s.is_cancelled', false);
            })
            ->where('a.player_id', $player->id)
            ->get(['a.id', 'a.counted', 's.slot']);

        $joined = $player->joined_at?->toDateString();
        $dates  = DB::table('absences')->where('player_id', $player->id)->pluck('attendance_date', 'id');

        // not a training day of the player, or before they joined
        $wrong = $rows->filter(fn ($r) => !$player->trainsOn($r->slot)
            || ($joined && Carbon::parse($dates[$r->id])->toDateString() < $joined));
        if ($wrong->isEmpty()) {
            return 0;
        }

        // drivers return counted as 1, "1" or true - compare as an integer
        $refund = $wrong->filter(fn ($r) => (int) $r->counted === 1)->count();
        DB::table('absences')->whereIn('id', $wrong->pluck('id'))->delete();
        if ($refund > 0) {
            DB::table('players')->where('id', $player->id)->increment('sessions_balance', $refund);
        }
        self::refreshReminder($player);

        return $wrong->count();
    }

    public static function balance(Player $player): int
    {
        return (int) DB::table('players')->where('id', $player->id)->value('sessions_balance');
    }

    /**
     * Reminder flag follows the balance: on at exactly one week left (or at a week or less with
     * $orLess, used when earlier sessions are entered), off when more than a week is left.
     */
    public static function refreshReminder(Player $player, bool $orLess = false): int
    {
        $balance   = self::balance($player);
        $threshold = $player->remindThreshold();

        if ($balance === $threshold || ($orLess && $balance <= $threshold)) {
            DB::table('players')->where('id', $player->id)->update(['reminder_pending' => true]);
        } elseif ($balance > $threshold) {
            DB::table('players')->where('id', $player->id)->update(['reminder_pending' => false]);
        }

        return $balance;
    }
}
