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

    /**
     * The practice days one payment pays for (receipt, client 3 Nov 2026). Sessions are used first in,
     * first out: every attended day and every counted absence uses the oldest paid session left, and
     * sessions entered as "already used" without a date come first. Days already used are returned
     * with their real date ('P' present / 'A' absent, 'B' = before the system, no date); the rest are
     * the next practice days on the player's own training days from the schedule (null = not on the
     * schedule yet). Returns a list of ['date' => Carbon|null, 'mark' => 'P'|'A'|'B'|null].
     */
    public static function paymentDates(\App\Models\Payment $payment): array
    {
        $player = $payment->player;
        if (!$player) {
            return [];
        }

        $payments = \App\Models\Payment::where('player_id', $player->id)->orderBy('paid_at')->orderBy('id')->get(['id', 'sessions', 'paid_at']);
        $before = 0;
        foreach ($payments as $p) {
            if ($p->id === $payment->id) {
                break;
            }
            $before += (int) $p->sessions;
        }

        $used = DB::table('attendances')->where('player_id', $player->id)->get(['attendance_date as d'])->map(fn ($r) => [Carbon::parse($r->d), 'P'])
            ->concat(DB::table('absences')->where('player_id', $player->id)->where('counted', true)->get(['attendance_date as d'])->map(fn ($r) => [Carbon::parse($r->d), 'A']))
            ->sortBy(fn ($u) => $u[0]->toDateString())->values();

        // sessions taken off without a date (earlier sessions when the player was added)
        $undated = max(0, (int) $payments->sum('sessions') - $used->count() - self::balance($player));
        $seq = array_merge(array_fill(0, $undated, [null, 'B']), $used->all());

        $out  = [];
        $need = (int) $payment->sessions;
        for ($i = $before; $i < $before + $need && $i < count($seq); $i++) {
            $out[] = ['date' => $seq[$i][0], 'mark' => $seq[$i][1]];
        }

        // not used yet: the coming practice days of this player
        if (count($out) < $need) {
            $last = $used->isNotEmpty() ? $used->last()[0]->toDateString() : null;
            $from = max($last ? Carbon::parse($last)->addDay()->toDateString() : '0000-00-00', $payment->paid_at->toDateString());
            $next = \App\Models\PracticeSession::where('is_cancelled', false)
                ->where('session_date', '>=', $from)
                ->orderBy('session_date')
                ->limit(200)
                ->get(['session_date', 'slot'])
                ->filter(fn ($s) => $player->trainsOn($s->slot))
                ->values();
            $k = 0;
            while (count($out) < $need) {
                $s = $next[$k++] ?? null;
                $out[] = ['date' => $s?->session_date, 'mark' => null];
            }
        }

        return $out;
    }

    /**
     * Client (20 Nov 2026): every practice the player should have come to (their training day, on or
     * after joining, held, already over) has a record. A day without one becomes "absent" - also for
     * players added later with an old joining date and for old practice days added to the schedule.
     * Then recount() decides which absences use a session. Returns how many absences were written.
     */
    public static function fillPastAbsences(Player $player): int
    {
        if (!$player->is_active) {
            return 0;
        }
        $joined = $player->joined_at?->toDateString();

        $marked = DB::table('attendances')->where('player_id', $player->id)->pluck('attendance_date')
            ->concat(DB::table('absences')->where('player_id', $player->id)->pluck('attendance_date'))
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();

        $rows = [];
        PracticeSession::where('is_cancelled', false)
            ->where('session_date', '<', today()->toDateString())
            ->when($joined, fn ($q) => $q->where('session_date', '>=', $joined))
            ->get(['session_date', 'slot'])
            ->each(function ($s) use ($player, $marked, &$rows) {
                $d = $s->session_date->toDateString();
                if ($player->trainsOn($s->slot) && !isset($marked[$d]) && !isset($rows[$d])) {
                    $rows[$d] = [
                        'player_id'       => $player->id,
                        'age_group'       => AgeGroup::forDob($player->dob, $d),
                        'attendance_date' => $d,
                        'counted'         => false,   // recount() sets it
                        'created_at'      => now(),
                    ];
                }
            });

        foreach (array_chunk(array_values($rows), 200) as $chunk) {
            DB::table('absences')->insertOrIgnore($chunk);
        }
        self::recount($player);

        return count($rows);
    }

    /**
     * Works out again, in date order, which absences use a session: payments add their sessions on the
     * day they were paid, every present day uses one, an absent day uses one only while sessions are
     * left. Sessions entered as "already used" (no date) are used first. Needed when old days, old
     * payments or old marks are entered later, so the order is the real one and not the typing order.
     * Updates absences.counted and the balance; returns the new balance.
     */
    public static function recount(Player $player): int
    {
        $pid  = $player->id;
        $pays = DB::table('payments')->where('player_id', $pid)->get(['paid_at', 'sessions']);
        $att  = DB::table('attendances')->where('player_id', $pid)->pluck('attendance_date');
        $abs  = DB::table('absences')->where('player_id', $pid)->get(['id', 'attendance_date', 'counted']);

        $paid    = (int) $pays->sum('sessions');
        $counted = $abs->filter(fn ($a) => (int) $a->counted === 1)->count();
        $undated = max(0, $paid - $att->count() - $counted - self::balance($player));

        // [date, order on that day (payment first), change, absence id]
        $events = [];
        foreach ($pays as $p) {
            $events[] = [Carbon::parse($p->paid_at)->toDateString(), 0, (int) $p->sessions, null];
        }
        foreach ($att as $d) {
            $events[] = [Carbon::parse($d)->toDateString(), 1, -1, null];
        }
        foreach ($abs as $a) {
            $events[] = [Carbon::parse($a->attendance_date)->toDateString(), 1, 0, $a];
        }
        usort($events, fn ($x, $y) => [$x[0], $x[1]] <=> [$y[0], $y[1]]);

        $run = -$undated;
        $on  = [];
        $off = [];
        foreach ($events as [$d, $o, $delta, $a]) {
            if ($a === null) {
                $run += $delta;
                continue;
            }
            $use = $run > 0;
            if ($use) {
                $run--;
            }
            if ($use !== ((int) $a->counted === 1)) {
                $use ? $on[] = $a->id : $off[] = $a->id;
            }
        }

        foreach (array_chunk($on, 500) as $c) {
            DB::table('absences')->whereIn('id', $c)->update(['counted' => true]);
        }
        foreach (array_chunk($off, 500) as $c) {
            DB::table('absences')->whereIn('id', $c)->update(['counted' => false]);
        }
        DB::table('players')->where('id', $pid)->update(['sessions_balance' => $run]);

        return self::refreshReminder($player);
    }

    /** Recount everyone who has an absence on one date (after a day was settled out of order). */
    public static function recountDate(string $date): void
    {
        $ids = DB::table('absences')->where('attendance_date', $date)->pluck('player_id');
        foreach (Player::whereIn('id', $ids)->get() as $p) {
            self::recount($p);
        }
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
