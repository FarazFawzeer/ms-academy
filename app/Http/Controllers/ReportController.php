<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $fromTs = $from . ' 00:00:00';
        $toTs   = $to . ' 23:59:59';

        // four headline numbers, one round trip
        $sum = DB::selectOne("
            SELECT
              (SELECT COUNT(*) FROM attendances WHERE attendance_date BETWEEN ? AND ?)                AS sessions,
              (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at BETWEEN ? AND ?)           AS revenue,
              (SELECT COALESCE(SUM(amount), 0) FROM coach_workdays WHERE work_date BETWEEN ? AND ?)   AS salary,
              (SELECT COUNT(*) FROM players WHERE joined_at BETWEEN ? AND ?)                          AS new_players
        ", [$from, $to, $fromTs, $toTs, $from, $to, $from, $to]);

        $byBatch = DB::table('attendances as a')
            ->leftJoin('batches as b', 'b.id', '=', 'a.batch_id')
            ->whereBetween('a.attendance_date', [$from, $to])
            ->groupBy('b.id', 'b.name')
            ->selectRaw('b.name as batch, COUNT(*) as sessions, COUNT(DISTINCT a.player_id) as players')
            ->orderByDesc('sessions')
            ->get();

        $byPlayer = DB::table('players as p')
            ->leftJoin('attendances as a', function ($j) use ($from, $to) {
                $j->on('a.player_id', '=', 'p.id')
                  ->where('a.attendance_date', '>=', $from)
                  ->where('a.attendance_date', '<=', $to);
            })
            ->leftJoin('batches as b', 'b.id', '=', 'p.batch_id')
            ->where('p.is_active', 1)
            ->groupBy('p.id', 'p.player_no', 'p.name', 'b.name')
            ->selectRaw('p.id, p.player_no, p.name, b.name as batch, COUNT(a.id) as sessions, MAX(a.attendance_date) as last_seen')
            ->orderByDesc('sessions')->orderBy('p.name')
            ->limit(300)
            ->get();

        return view('reports.index', compact('sum', 'byBatch', 'byPlayer', 'from', 'to'));
    }

    public function export(Request $request, string $type)
    {
        [$from, $to] = $this->range($request);

        switch ($type) {
            case 'attendance':
                $rows = DB::table('attendances as a')
                    ->join('players as p', 'p.id', '=', 'a.player_id')
                    ->leftJoin('batches as b', 'b.id', '=', 'a.batch_id')
                    ->whereBetween('a.attendance_date', [$from, $to])
                    ->orderBy('a.attendance_date')->orderBy('p.name')
                    ->get(['a.attendance_date', 'p.player_no', 'p.name', 'b.name as batch', 'a.method'])
                    ->map(fn ($r) => (array) $r);
                $headings = ['Date', 'Player No', 'Player', 'Batch', 'Method'];
                break;

            case 'payments':
                $rows = DB::table('payments as pay')
                    ->join('players as p', 'p.id', '=', 'pay.player_id')
                    ->whereBetween('pay.paid_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orderBy('pay.paid_at')
                    ->get(['pay.paid_at', 'p.player_no', 'p.name', 'pay.amount', 'pay.sessions', 'pay.method', 'pay.note'])
                    ->map(fn ($r) => (array) $r);
                $headings = ['Paid At', 'Player No', 'Player', 'Amount (LKR)', 'Sessions', 'Method', 'Note'];
                break;

            case 'salary':
                $rows = DB::table('coach_workdays as w')
                    ->join('coaches as c', 'c.id', '=', 'w.coach_id')
                    ->whereBetween('w.work_date', [$from, $to])
                    ->groupBy('c.id', 'c.name')
                    ->selectRaw('c.name, COUNT(*) as days, SUM(w.amount) as total,
                        SUM(CASE WHEN w.paid_at IS NULL THEN w.amount ELSE 0 END) as unpaid')
                    ->orderBy('c.name')
                    ->get()->map(fn ($r) => (array) $r);
                $headings = ['Coach', 'Days Worked', 'Total (LKR)', 'Unpaid (LKR)'];
                break;

            default:
                abort(404);
        }

        return Excel::download(new ReportExport($rows, $headings), "{$type}_{$from}_to_{$to}.xlsx");
    }

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }

        return $from > $to ? [$to, $from] : [$from, $to];
    }
}
