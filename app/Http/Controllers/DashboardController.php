<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();
        $thisStart = now()->startOfMonth()->toDateTimeString();
        $thisEnd   = now()->endOfMonth()->toDateTimeString();
        $lastStart = now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString();
        $lastEnd   = now()->subMonthNoOverflow()->endOfMonth()->toDateTimeString();

        // every headline number in ONE round trip (all indexed / tiny tables)
        $stats = DB::selectOne("
            SELECT
              (SELECT COUNT(*) FROM players WHERE is_active = 1)                                   AS active_players,
              (SELECT COUNT(*) FROM players WHERE is_active = 1 AND created_at >= ?)               AS new_players,
              (SELECT COUNT(*) FROM attendances WHERE attendance_date = ?)                         AS today_count,
              (SELECT COUNT(*) FROM attendances WHERE attendance_date = ?)                         AS yesterday_count,
              (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at BETWEEN ? AND ?)        AS month_revenue,
              (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at BETWEEN ? AND ?)        AS last_month_revenue,
              (SELECT COUNT(*) FROM players WHERE is_active = 1 AND reminder_pending = 1)          AS reminders,
              (SELECT COUNT(*) FROM players WHERE is_active = 1 AND sessions_balance < 0)          AS payment_due
        ", [$thisStart, $today, $yesterday, $thisStart, $thisEnd, $lastStart, $lastEnd]);

        // last 30 days, zero-filled (the page can show 7 / 14 / 30 from this one query)
        $raw = Attendance::where('attendance_date', '>=', today()->subDays(29)->toDateString())
            ->selectRaw('attendance_date as d, COUNT(*) as c')
            ->groupBy('attendance_date')
            ->pluck('c', 'd');

        $chart = ['labels' => [], 'values' => []];
        for ($i = 29; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $chart['labels'][] = $day->format('d M');
            $chart['values'][] = (int) ($raw[$day->toDateString()] ?? 0);
        }

        $cols = ['id', 'player_no', 'name', 'photo_path', 'batch_id', 'guardian_name', 'guardian_phone', 'sessions_balance'];

        $reminders = Player::active()->where('reminder_pending', true)->with('batch:id,name')
            ->orderBy('name')->limit(6)->get($cols);

        $dues = Player::active()->where('sessions_balance', '<', 0)->with('batch:id,name')
            ->orderBy('sessions_balance')->limit(6)->get($cols);

        $recent = Attendance::join('players', 'players.id', '=', 'attendances.player_id')
            ->where('attendances.attendance_date', $today)
            ->orderByDesc('attendances.id')->limit(5)
            ->get(['attendances.id', 'attendances.created_at', 'players.name', 'players.player_no', 'players.photo_path']);

        return view('dashboard', compact('stats', 'chart', 'reminders', 'dues', 'recent'));
    }
}