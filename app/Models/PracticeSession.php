<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * One practice day. Regular Friday / Sunday sessions are created automatically by generate();
 * the admin can move one (session_date != planned_date), change its time or cancel it.
 * The schedule is the same for the whole academy.
 */
class PracticeSession extends Model
{
    protected $fillable = ['slot', 'planned_date', 'session_date', 'start_time', 'end_time', 'is_cancelled', 'note'];

    protected $casts = [
        'planned_date' => 'date',
        'session_date' => 'date',
        'is_cancelled' => 'boolean',
        'closed_at'    => 'datetime',
    ];

    /* ---------- dates are always stored as plain Y-m-d (exact-date lookups depend on it) ---------- */

    public function setSessionDateAttribute($value): void
    {
        $this->attributes['session_date'] = Carbon::parse($value)->toDateString();
    }

    public function setPlannedDateAttribute($value): void
    {
        $this->attributes['planned_date'] = Carbon::parse($value)->toDateString();
    }

    /* ---------- regular days (config academy.training_days) ---------- */

    /** ['fri' => ['label' => 'Friday', ...], 'sun' => [...]] */
    public static function slots(): array
    {
        return (array) config('academy.training_days', []);
    }

    public static function slotLabel(?string $slot): string
    {
        return self::slots()[$slot]['label'] ?? ucfirst((string) $slot);
    }

    /**
     * Create the regular sessions between two dates (never before academy.schedule_start).
     * INSERT OR IGNORE on (slot, planned_date): moved or cancelled sessions are never re-created.
     */
    public static function generate($from, $to): void
    {
        $from  = Carbon::parse($from)->startOfDay();
        $to    = Carbon::parse($to)->startOfDay();
        $start = Carbon::parse(config('academy.schedule_start', today()->toDateString()))->startOfDay();
        if ($from->lt($start)) {
            $from = $start;
        }
        if ($from->gt($to)) {
            return;
        }

        $byWeekday = [];
        foreach (self::slots() as $key => $s) {
            $byWeekday[(int) $s['weekday']] = $key;
        }

        $rows = [];
        $now  = now();
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $slot = $byWeekday[$d->dayOfWeek] ?? null;
            if ($slot === null) {
                continue;
            }
            $rows[] = [
                'slot'         => $slot,
                'planned_date' => $d->toDateString(),
                'session_date' => $d->toDateString(),
                'start_time'   => self::slots()[$slot]['start'] ?? null,
                'end_time'     => self::slots()[$slot]['end'] ?? null,
                'is_cancelled' => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if ($rows) {
            DB::table('practice_sessions')->insertOrIgnore($rows);
        }
    }

    /** Fill the usual window: from a few weeks back to N weeks ahead. */
    public static function fillAround($date = null): void
    {
        $d = $date ? Carbon::parse($date) : today();
        self::generate($d->copy()->subWeeks(5), $d->copy()->addWeeks((int) config('academy.schedule_weeks_ahead', 8)));
    }

    /** The (not cancelled) session held on a date, or null when there is no practice that day. */
    public static function forDate($date): ?self
    {
        $date = Carbon::parse($date)->toDateString();
        self::generate($date, $date);

        return static::where('session_date', $date)->where('is_cancelled', false)->orderBy('start_time')->first();
    }

    /* ---------- settling finished practice days ---------- */

    /**
     * Settles every finished practice day once (runs on each page load, costs one small query
     * when there is nothing to do). For each active player who trains on that day, joined by then
     * and was not marked present: an absence is written and, if they still have sessions left,
     * one session is used. A used-up package is not taken below 0 by absences.
     */
    public static function closePending(): int
    {
        $today = today()->toDateString();
        $start = Carbon::parse(config('academy.schedule_start', $today))->toDateString();

        // make sure last week's regular days exist even if nobody opened the schedule
        self::generate(today()->subDays(14), today()->subDay());

        $ids = static::where('is_cancelled', false)
            ->whereNull('closed_at')
            ->where('session_date', '<', $today)
            ->where('session_date', '>=', $start)
            ->orderBy('session_date')
            ->pluck('id');

        $closed = 0;
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$closed) {
                // claim the day first: two requests at the same moment can never settle it twice
                if (!DB::table('practice_sessions')->where('id', $id)->whereNull('closed_at')->update(['closed_at' => now()])) {
                    return;
                }
                $closed++;

                $s       = static::find($id);
                $date    = $s->session_date->toDateString();
                $present = Attendance::where('attendance_date', $date)->pluck('player_id')->flip();

                $players = Player::active()
                    ->trainingDays($s->slot)
                    ->where(fn ($q) => $q->whereNull('joined_at')->orWhere('joined_at', '<=', $date))
                    ->get(['id', 'dob', 'training_days', 'sessions_balance']);

                foreach ($players as $p) {
                    if (isset($present[$p->id])) {
                        continue;
                    }
                    $counted = (int) $p->sessions_balance > 0;

                    $inserted = DB::table('absences')->insertOrIgnore([
                        'player_id'       => $p->id,
                        'age_group'       => \App\Support\AgeGroup::forDob($p->dob, $date),
                        'attendance_date' => $date,
                        'counted'         => $counted,
                        'created_at'      => now(),
                    ]);

                    if ($inserted && $counted) {
                        DB::table('players')->where('id', $p->id)->decrement('sessions_balance');
                        if ((int) $p->sessions_balance - 1 === $p->remindThreshold()) {
                            DB::table('players')->where('id', $p->id)->update(['reminder_pending' => true]);
                        }
                    }
                }
            });
        }

        return $closed;
    }

    /* ---------- helpers ---------- */

    public function scopeActive($q)
    {
        return $q->where('is_cancelled', false);
    }

    public function getLabelAttribute(): string
    {
        return self::slotLabel($this->slot) . ' practice';
    }

    public function getIsMovedAttribute(): bool
    {
        return !$this->session_date->isSameDay($this->planned_date);
    }

    /** "4:00 PM - 5:30 PM" */
    public function getTimeTextAttribute(): string
    {
        $f = fn ($t) => $t ? Carbon::parse($t)->format('g:i A') : null;
        $s = $f($this->start_time);
        $e = $f($this->end_time);

        return $s && $e ? $s . ' - ' . $e : ($s ?? '');
    }

    public function attendanceCount(): int
    {
        return Attendance::where('attendance_date', $this->session_date->toDateString())->count();
    }
}
