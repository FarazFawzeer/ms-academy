<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Player;
use App\Models\PracticeSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Practice schedule (Admin only). The admin picks the practice days on a month calendar - many at
 * once - for Friday players, Sunday players or all players (client, 29 Oct 2026). Single days can
 * still be moved, re-timed, cancelled or put back from the list. A day that already has attendance
 * is locked (date, cancel, remove), so history stays correct. One practice per date.
 */
class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $view  = $request->query('view') === 'past' ? 'past' : 'upcoming';
        $weeks = (int) config('academy.schedule_weeks_ahead', 8);

        // ---- month calendar ----
        $mParam = (string) $request->query('month');
        $month  = preg_match('/^\d{4}-\d{2}$/', $mParam) ? Carbon::createFromFormat('Y-m-d', $mParam . '-01') : today()->startOfMonth();
        $gridFrom = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridTo   = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $inGrid = PracticeSession::whereBetween('session_date', [$gridFrom->toDateString(), $gridTo->toDateString()])
            ->orderBy('is_cancelled')->orderBy('start_time')->get()
            ->groupBy(fn ($s) => $s->session_date->toDateString());

        // ---- list below the calendar ----
        $q = PracticeSession::query();
        if ($view === 'past') {
            $q->where('session_date', '<', today()->toDateString())
              ->where('session_date', '>=', today()->subWeeks($weeks)->toDateString())
              ->orderByDesc('session_date');
        } else {
            $q->where('session_date', '>=', today()->toDateString())
              ->where('session_date', '<=', today()->addWeeks($weeks)->toDateString())
              ->orderBy('session_date');
        }
        $sessions = $q->orderBy('start_time')->get();

        // players marked per date (one grouped query) for the list and the calendar
        // toBase(): with no sessions, an empty Eloquent collection would try to merge the date strings as models
        $dates  = $sessions->toBase()->map(fn ($s) => $s->session_date->toDateString())->merge($inGrid->keys())->unique()->values();
        $marked = Attendance::whereIn('attendance_date', $dates)
            ->selectRaw('attendance_date, COUNT(*) as c')
            ->groupBy('attendance_date')
            ->pluck('c', 'attendance_date')
            ->mapWithKeys(fn ($c, $d) => [Carbon::parse($d)->toDateString() => (int) $c]);

        // players expected per group (Friday players, Sunday players, all players)
        $expected = [];
        foreach (array_keys(PracticeSession::groups()) as $slot) {
            $expected[$slot] = Player::active()->trainingDays($slot === 'all' ? null : $slot)->count();
        }

        return view('schedule.index', [
            'sessions' => $sessions,
            'marked'   => $marked,
            'expected' => $expected,
            'view'     => $view,
            'slots'    => PracticeSession::slots(),
            'groups'   => PracticeSession::groups(),
            'month'    => $month,
            'gridFrom' => $gridFrom,
            'gridTo'   => $gridTo,
            'inGrid'   => $inGrid,
        ]);
    }

    /** Add one practice day (still used by the "Add Practice" popup). */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'slot'         => ['required', Rule::in(array_keys(PracticeSession::groups()))],
            'session_date' => 'required|date|after_or_equal:today',
        ] + $this->timeRules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $date = Carbon::parse($request->input('session_date'))->toDateString();
        if ($clash = $this->clash($date)) {
            return response()->json(['success' => false, 'message' => $clash], 422);
        }

        $this->addDay($date, $request->input('slot'), $request->input('start_time'), $request->input('end_time'), $request->input('note'));

        return response()->json(['success' => true, 'message' => 'Practice added for ' . Carbon::parse($date)->format('D, d M') . '.']);
    }

    /**
     * Calendar: one action for many selected days at once.
     *  add     = new practice on each day (skips days that already have one)
     *  cancel  = "no practice" on each day (skips days with attendance)
     *  restore = put cancelled days back on
     *  remove  = delete days added by mistake (today or later, no attendance)
     */
    public function bulk(Request $request)
    {
        $action = $request->input('action');
        $v = Validator::make($request->all(), [
            'action'  => 'required|in:add,cancel,restore,remove',
            'dates'   => 'required|array|min:1|max:93',
            'dates.*' => 'required|date',
            // 'auto' = by day: Fridays for Friday players, Sundays for Sunday players
            'slot'    => [$action === 'add' ? 'required' : 'nullable', Rule::in(array_merge(array_keys(PracticeSession::groups()), ['auto']))],
        ] + $this->timeRules(), $this->messages() + [
            'dates.required' => 'Select at least one day on the calendar.',
            'slot.required'  => 'Choose who the practice is for.',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $today = today()->toDateString();
        $dates = collect($request->input('dates'))->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->sort()->values();
        $done  = 0;
        $skip  = [];   // reason => [dates]
        $fmt   = fn ($d) => Carbon::parse($d)->format('D d M');

        DB::transaction(function () use ($action, $dates, $request, $today, &$done, &$skip, $fmt) {
            foreach ($dates as $date) {
                $active    = PracticeSession::where('session_date', $date)->where('is_cancelled', false)->get();
                $cancelled = PracticeSession::where('session_date', $date)->where('is_cancelled', true)->get();
                $marked    = $this->hasAttendance($date);

                if ($action === 'add') {
                    $slot = $request->input('slot');
                    if ($slot === 'auto') {
                        $wd   = Carbon::parse($date)->dayOfWeek;
                        $slot = collect(PracticeSession::slots())->search(fn ($s) => (int) $s['weekday'] === $wd) ?: null;
                    }
                    if ($slot === null) {
                        $skip['not a Friday or Sunday - choose a group for it'][] = $fmt($date);
                    } elseif ($date < $today) {
                        $skip['in the past'][] = $fmt($date);
                    } elseif ($active->isNotEmpty()) {
                        $skip['already a practice'][] = $fmt($date);
                    } else {
                        $this->addDay($date, $slot, $request->input('start_time'), $request->input('end_time'), $request->input('note'));
                        $done++;
                    }
                } elseif ($action === 'cancel') {
                    if ($active->isEmpty()) {
                        $skip['no practice to cancel'][] = $fmt($date);
                    } elseif ($marked) {
                        $skip['players already marked'][] = $fmt($date);
                    } else {
                        foreach ($active as $s) {
                            $s->update(['is_cancelled' => true, 'note' => $request->input('note') ?: $s->note]);
                        }
                        $done++;
                    }
                } elseif ($action === 'restore') {
                    if ($cancelled->isEmpty()) {
                        $skip['not cancelled'][] = $fmt($date);
                    } elseif ($active->isNotEmpty()) {
                        $skip['already a practice'][] = $fmt($date);
                    } else {
                        $cancelled->first()->update(['is_cancelled' => false]);
                        $done++;
                    }
                } else { // remove
                    if ($active->isEmpty() && $cancelled->isEmpty()) {
                        $skip['nothing to remove'][] = $fmt($date);
                    } elseif ($date < $today) {
                        $skip['past days stay for the records - cancel instead'][] = $fmt($date);
                    } elseif ($marked) {
                        $skip['players already marked'][] = $fmt($date);
                    } else {
                        PracticeSession::where('session_date', $date)->delete();
                        $done++;
                    }
                }
            }
        });

        $verb = ['add' => 'added', 'cancel' => 'cancelled', 'restore' => 'put back on', 'remove' => 'removed'][$action];
        $msg  = $done . ' day(s) ' . $verb . '.';
        foreach ($skip as $why => $list) {
            $msg .= ' Skipped (' . $why . '): ' . implode(', ', $list) . '.';
        }

        return response()->json(['success' => $done > 0, 'message' => $msg, 'done' => $done], $done > 0 ? 200 : 422);
    }

    /** Move to another date and/or change the time and note. */
    public function update(Request $request, PracticeSession $practice)
    {
        $v = Validator::make($request->all(), [
            'session_date' => 'required|date',
        ] + $this->timeRules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $newDate = Carbon::parse($request->input('session_date'))->toDateString();
        $oldDate = $practice->session_date->toDateString();

        if ($newDate !== $oldDate) {
            if ($this->hasAttendance($oldDate)) {
                return response()->json(['success' => false, 'message' => 'Players were already marked on ' . $practice->session_date->format('D, d M') . ', so this practice cannot be moved.'], 422);
            }
            if ($newDate < today()->toDateString()) {
                return response()->json(['success' => false, 'message' => 'A practice can only be moved to today or a later date.'], 422);
            }
            if (!$practice->is_cancelled && ($clash = $this->clash($newDate, $practice->id))) {
                return response()->json(['success' => false, 'message' => $clash], 422);
            }
        }

        $practice->update([
            'session_date' => $newDate,
            'start_time'   => $request->input('start_time'),
            'end_time'     => $request->input('end_time'),
            'note'         => $request->input('note'),
        ]);

        return response()->json(['success' => true, 'message' => $practice->label . ' saved for ' . $practice->session_date->format('D, d M') . '.']);
    }

    public function cancel(Request $request, PracticeSession $practice)
    {
        if ($this->hasAttendance($practice->session_date->toDateString())) {
            return response()->json(['success' => false, 'message' => 'Players were already marked on this day. Undo them on the scan page first.'], 422);
        }

        $practice->update(['is_cancelled' => true, 'note' => $request->input('note') ?: $practice->note]);

        return response()->json(['success' => true, 'message' => 'Practice on ' . $practice->session_date->format('D, d M') . ' cancelled.']);
    }

    public function restore(PracticeSession $practice)
    {
        if ($clash = $this->clash($practice->session_date->toDateString(), $practice->id)) {
            return response()->json(['success' => false, 'message' => $clash], 422);
        }

        $practice->update(['is_cancelled' => false]);

        return response()->json(['success' => true, 'message' => 'Practice on ' . $practice->session_date->format('D, d M') . ' is back on.']);
    }

    /* ---------- helpers ---------- */

    /** New practice on a date (a cancelled one of the same group on that date is simply restored). */
    private function addDay(string $date, string $slot, ?string $start, ?string $end, ?string $note): void
    {
        [$defStart, $defEnd] = PracticeSession::defaultTimes($slot);

        $s = PracticeSession::firstOrNew(['slot' => $slot, 'planned_date' => $date]);
        $s->fill([
            'session_date' => $date,
            'start_time'   => $start ?: $defStart,
            'end_time'     => $end ?: $defEnd,
            'note'         => $note,
            'is_cancelled' => false,
        ])->save();
    }

    private function timeRules(): array
    {
        return [
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i|after:start_time',
            'note'       => 'nullable|string|max:150',
        ];
    }

    private function messages(): array
    {
        return [
            'session_date.after_or_equal' => 'Choose today or a later date.',
            'end_time.after'              => 'The end time must be after the start time.',
        ];
    }

    private function hasAttendance(string $date): bool
    {
        return Attendance::where('attendance_date', $date)->exists();
    }

    /** One practice per day: returns an error message when the date already has one. */
    private function clash(string $date, ?int $exceptId = null): ?string
    {
        $other = PracticeSession::where('session_date', $date)->where('is_cancelled', false)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();

        return $other
            ? Carbon::parse($date)->format('D, d M') . ' already has the ' . $other->label . '. Cancel or move that one first.'
            : null;
    }
}
