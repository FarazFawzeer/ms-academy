<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Player;
use App\Models\PracticeSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Practice schedule (Admin only). Regular Friday / Sunday sessions appear by themselves;
 * the admin can move one to another date, change its time, cancel it, or add an extra one.
 * A session that already has attendance is locked (date and cancel), so history stays correct.
 */
class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $view  = $request->query('view') === 'past' ? 'past' : 'upcoming';
        $weeks = (int) config('academy.schedule_weeks_ahead', 8);

        PracticeSession::fillAround(today());

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

        // players marked per date (one grouped query) and players expected per regular day
        $marked = Attendance::whereIn('attendance_date', $sessions->map(fn ($s) => $s->session_date->toDateString()))
            ->selectRaw('attendance_date, COUNT(*) as c')
            ->groupBy('attendance_date')
            ->pluck('c', 'attendance_date')
            ->mapWithKeys(fn ($c, $d) => [Carbon::parse($d)->toDateString() => (int) $c]);

        $expected = [];
        foreach (array_keys(PracticeSession::slots()) as $slot) {
            $expected[$slot] = Player::active()->trainingDays($slot)->count();
        }

        return view('schedule.index', [
            'sessions' => $sessions,
            'marked'   => $marked,
            'expected' => $expected,
            'view'     => $view,
            'slots'    => PracticeSession::slots(),
        ]);
    }

    /** Add an extra practice (e.g. a make-up Saturday for the Sunday players). */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'slot'         => ['required', Rule::in(array_keys(PracticeSession::slots()))],
            'session_date' => 'required|date|after_or_equal:today',
        ] + $this->timeRules(), $this->messages());
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $date = Carbon::parse($request->input('session_date'))->toDateString();
        PracticeSession::generate($date, $date);

        if ($clash = $this->clash($date)) {
            return response()->json(['success' => false, 'message' => $clash], 422);
        }

        // a cancelled regular session on that date for the same day is simply restored
        $s = PracticeSession::firstOrNew(['slot' => $request->input('slot'), 'planned_date' => $date]);
        $s->fill([
            'session_date' => $date,
            'start_time'   => $request->input('start_time'),
            'end_time'     => $request->input('end_time'),
            'note'         => $request->input('note'),
            'is_cancelled' => false,
        ])->save();

        return response()->json(['success' => true, 'message' => 'Practice added for ' . Carbon::parse($date)->format('D, d M') . '.']);
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
            PracticeSession::generate($newDate, $newDate);
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
