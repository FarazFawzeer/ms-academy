<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use App\Models\CoachWorkday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoachWorkdayController extends Controller
{
    /** Daily coach attendance: one switch per coach. */
    public function daily(Request $request)
    {
        $date = $this->parseDate($request->query('date'));

        $coaches = Coach::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'phone', 'photo_path', 'daily_rate']);

        // coach_id => paid_at (null when unpaid). Presence of the key = worked that day.
        $worked = CoachWorkday::where('work_date', $date)->pluck('paid_at', 'coach_id');

        return view('coaches.workdays', compact('coaches', 'worked', 'date'));
    }

    public function toggle(Request $request, Coach $coach)
    {
        $v = Validator::make($request->all(), [
            'date'    => 'required|date|before_or_equal:today',
            'present' => 'required|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $date = Carbon::parse($request->input('date'))->toDateString();

        if ($request->boolean('present')) {
            // INSERT OR IGNORE: tapping twice can never create a duplicate day
            DB::table('coach_workdays')->insertOrIgnore([
                'coach_id'  => $coach->id,
                'work_date' => $date,
                'amount'    => $coach->daily_rate,
            ]);

            return response()->json(['success' => true, 'message' => $coach->name . ' marked present.']);
        }

        $deleted = DB::table('coach_workdays')
            ->where('coach_id', $coach->id)->where('work_date', $date)->whereNull('paid_at')->delete();

        if (!$deleted && DB::table('coach_workdays')->where('coach_id', $coach->id)->where('work_date', $date)->exists()) {
            return response()->json(['success' => false, 'message' => 'This day is already paid and cannot be changed.'], 422);
        }

        return response()->json(['success' => true, 'message' => $coach->name . ' marked absent.']);
    }

    /** Monthly salary summary: days, total, still unpaid. One grouped query. */
    public function salary(Request $request)
    {
        [$month, $from, $to] = $this->month($request->query('month'));

        $rows = Coach::query()
            ->leftJoin('coach_workdays as w', function ($j) use ($from, $to) {
                $j->on('w.coach_id', '=', 'coaches.id')
                  ->where('w.work_date', '>=', $from)
                  ->where('w.work_date', '<=', $to);
            })
            ->groupBy('coaches.id', 'coaches.name', 'coaches.daily_rate', 'coaches.photo_path', 'coaches.is_active')
            ->selectRaw('coaches.id, coaches.name, coaches.daily_rate, coaches.photo_path, coaches.is_active,
                COUNT(w.id) as days,
                COALESCE(SUM(w.amount), 0) as total,
                COALESCE(SUM(CASE WHEN w.paid_at IS NULL THEN w.amount ELSE 0 END), 0) as unpaid')
            ->orderBy('coaches.name')
            ->get()
            ->filter(fn ($r) => $r->is_active || $r->days > 0)
            ->values();

        $sum = ['total' => $rows->sum('total'), 'unpaid' => $rows->sum('unpaid'), 'days' => $rows->sum('days')];

        return view('coaches.salary', compact('rows', 'sum', 'month'));
    }

    /** JSON list of the days a coach worked in a month (for the "View days" popup). */
    public function days(Request $request, Coach $coach)
    {
        [, $from, $to] = $this->month($request->query('month'));

        $days = CoachWorkday::where('coach_id', $coach->id)
            ->whereBetween('work_date', [$from, $to])
            ->orderBy('work_date')
            ->get(['work_date', 'amount', 'paid_at'])
            ->map(fn ($d) => [
                'date'   => $d->work_date->format('D, d M'),
                'amount' => number_format((float) $d->amount),
                'paid'   => $d->paid_at !== null,
            ]);

        return response()->json($days);
    }

    public function pay(Request $request)
    {
        $v = Validator::make($request->all(), [
            'coach_id' => 'required|exists:coaches,id',
            'month'    => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        [, $from, $to] = $this->month($request->input('month'));

        $count = DB::table('coach_workdays')
            ->where('coach_id', $request->input('coach_id'))
            ->whereBetween('work_date', [$from, $to])
            ->whereNull('paid_at')
            ->update(['paid_at' => now()]);

        return response()->json(['success' => true, 'message' => $count . ' day(s) marked as paid.']);
    }

    private function parseDate(?string $value): string
    {
        try {
            $d = $value ? Carbon::parse($value) : today();
        } catch (\Throwable $e) {
            $d = today();
        }

        return $d->isFuture() ? today()->toDateString() : $d->toDateString();
    }

    /** @return array{0:string,1:string,2:string} [YYYY-MM, first day, last day] */
    private function month(?string $value): array
    {
        try {
            $m = $value && preg_match('/^\d{4}-\d{2}$/', $value) ? Carbon::createFromFormat('Y-m-d', $value . '-01') : today();
        } catch (\Throwable $e) {
            $m = today();
        }

        return [$m->format('Y-m'), $m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()];
    }
}
