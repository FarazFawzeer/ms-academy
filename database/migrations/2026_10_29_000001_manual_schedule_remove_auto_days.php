<?php

use App\Models\PracticeSession;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client (29 Oct 2026): the admin now picks every practice day on the calendar, nothing is created
 * automatically any more. This removes the future Fridays / Sundays the old automatic schedule made
 * that nobody touched (same date as planned, default time, no note, not cancelled, nobody marked),
 * so the calendar starts clean. Past days, today and anything the admin changed are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (config('academy.auto_schedule', false)) {
            return;
        }

        $ids = [];
        foreach (PracticeSession::slots() as $slot => $s) {
            $rows = DB::table('practice_sessions as p')
                ->where('p.slot', $slot)
                ->where('p.session_date', '>', today()->toDateString())
                ->whereColumn('p.session_date', 'p.planned_date')
                ->where('p.is_cancelled', false)
                ->whereNull('p.note')
                ->whereNull('p.closed_at')
                ->whereNotExists(fn ($q) => $q->from('attendances as a')->whereColumn('a.attendance_date', 'p.session_date'))
                ->get(['p.id', 'p.session_date', 'p.start_time', 'p.end_time']);

            foreach ($rows as $r) {
                // only the regular weekday at the regular time = made by the old automatic schedule
                if (Carbon::parse($r->session_date)->dayOfWeek === (int) $s['weekday']
                    && substr((string) $r->start_time, 0, 5) === $s['start']
                    && substr((string) $r->end_time, 0, 5) === $s['end']) {
                    $ids[] = $r->id;
                }
            }
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::table('practice_sessions')->whereIn('id', $chunk)->delete();
        }
    }

    public function down(): void
    {
        // data clean-up only: turn ACADEMY_AUTO_SCHEDULE on to have regular days created again
    }
};
