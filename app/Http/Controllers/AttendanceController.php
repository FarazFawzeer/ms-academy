<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Absence;
use App\Models\Attendance;
use App\Models\Player;
use App\Models\PracticeSession;
use App\Support\AgeGroup;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    /** Scan + search + today's list on one screen. */
    public function scan()
    {
        $session  = PracticeSession::forDate(today());
        $expected = $session ? Player::active()->trainingDays($session->slot)->count() : 0;

        $today = Attendance::query()
            ->join('players', 'players.id', '=', 'attendances.player_id')
            ->where('attendances.attendance_date', today()->toDateString())
            ->orderByDesc('attendances.id')
            ->get([
                'attendances.id', 'attendances.created_at', 'attendances.method', 'attendances.age_group',
                'attendances.is_extra', 'players.name', 'players.player_no', 'players.photo_path',
            ]);

        return view('attendance.scan', compact('today', 'session', 'expected'));
    }

    /**
     * Mark by QR token (scanner) or player_id (manual search).
     * Only on a practice day, and only if the player trains on that day. An Admin may override
     * (force=1) for a make-up class; that attendance is saved as "extra".
     */
    public function mark(Request $request)
    {
        $token = strtoupper(trim((string) $request->input('token')));
        $cols  = ['id', 'player_no', 'name', 'dob', 'photo_path', 'training_days', 'sessions_balance', 'is_active'];

        $player = $token !== ''
            ? Player::where('qr_token', $token)->first($cols)
            : Player::find((int) $request->input('player_id'), $cols);

        if (!$player) {
            return response()->json(['success' => false, 'status' => 'not_found', 'message' => 'QR code not recognised.'], 404);
        }

        $isAdmin = in_array($request->user()?->type, ['Super Admin', 'Admin'], true);
        $info    = [
            'id'    => $player->id,
            'name'  => $player->name,
            'no'    => $player->player_no,
            'photo' => $player->photo_url,
            'group' => $player->age_group,
            'days'  => $player->days_text,
            'fee'   => $player->planFee(),
            'plan'  => $player->planSessions(),
        ];

        if (!$player->is_active) {
            return response()->json(['success' => false, 'status' => 'inactive', 'message' => 'This player is inactive.', 'player' => $info], 422);
        }

        $today = today()->toDateString();

        if (Attendance::where('player_id', $player->id)->where('attendance_date', $today)->exists()) {
            return $this->duplicate($player, $info);
        }

        // ---- is today a training day for this player? ----
        $session = PracticeSession::forDate($today);
        $force   = $isAdmin && $request->boolean('force');
        $extra   = false;

        if (!$session || !$player->trainsOn($session->slot)) {
            if (!$force) {
                $message = !$session
                    ? 'There is no practice scheduled today.'
                    : $player->name . ' trains on ' . $player->days_text . '. Today is ' . $session->label . '.';

                return response()->json([
                    'success'      => false,
                    'status'       => $session ? 'wrong_day' : 'no_practice',
                    'message'      => $message,
                    'can_override' => $isAdmin,
                    'player'       => $info,
                ], 422);
            }
            $extra = true;
        }

        try {
            [$attendanceId, $balance, $remind] = DB::transaction(function () use ($player, $today, $request, $token, $extra) {
                // the UNIQUE(player_id, attendance_date) index rejects a double scan, even from two phones at once
                $id = DB::table('attendances')->insertGetId([
                    'player_id'       => $player->id,
                    'age_group'       => $player->age_group,
                    'attendance_date' => $today,
                    'method'          => $token !== '' ? 'qr' : 'manual',
                    'is_extra'        => $extra,
                    'marked_by'       => $request->user()?->id,
                    'created_at'      => now(),
                ]);

                DB::table('players')->where('id', $player->id)->decrement('sessions_balance');
                $balance = (int) DB::table('players')->where('id', $player->id)->value('sessions_balance');

                $remind = $balance === $player->remindThreshold();
                if ($remind) {
                    DB::table('players')->where('id', $player->id)->update(['reminder_pending' => true]);
                }

                return [$id, $balance, $remind];
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? '') !== '23000') {
                throw $e;
            }

            return $this->duplicate($player, $info);
        }

        return response()->json([
            'success'       => true,
            'status'        => 'marked',
            'message'       => $extra ? 'Marked as an extra session.' : 'Attendance marked.',
            'player'        => $info,
            'attendance_id' => $attendanceId,
            'extra'         => $extra,
            'session'       => $balance < 0 ? null : $player->sessionNumber($balance),
            'total'         => $player->planSessions(),
            'balance'       => $balance,
            'payment_due'   => $balance < 0,
            'reminder'      => $remind,
            'time'          => now()->format('h:i A'),
        ]);
    }

    private function duplicate(Player $player, array $info)
    {
        return response()->json([
            'success' => false,
            'status'  => 'duplicate',
            'message' => $player->name . ' is already marked present today.',
            'player'  => $info,
        ]);
    }

    /** Quick manual search used on the scan screen. */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $session = PracticeSession::forDate(today());

        $rows = Player::active()
            ->search($q)
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'player_no', 'name', 'dob', 'photo_path', 'training_days', 'sessions_balance']);

        return response()->json($rows->map(fn (Player $p) => [
            'id'        => $p->id,
            'name'      => $p->name,
            'no'        => $p->player_no,
            'photo'     => $p->photo_url,
            'group'     => $p->age_group,
            'days'      => $p->days_text,
            'not_today' => !$session || !$p->trainsOn($session->slot),
            'badge'     => (string) $p->balanceBadge(),
        ]));
    }

    /** Undo a wrong scan (today only). Gives the session back. */
    public function destroy(Attendance $attendance)
    {
        if (!$attendance->attendance_date->isToday()) {
            return response()->json(['success' => false, 'message' => 'Only today\'s attendance can be undone.'], 422);
        }

        $player = Player::find($attendance->player_id, ['id', 'training_days']);

        DB::transaction(function () use ($attendance, $player) {
            DB::table('attendances')->where('id', $attendance->id)->delete();
            DB::table('players')->where('id', $attendance->player_id)->increment('sessions_balance');

            $balance = (int) DB::table('players')->where('id', $attendance->player_id)->value('sessions_balance');
            if ($player && $balance > $player->remindThreshold()) {
                DB::table('players')->where('id', $attendance->player_id)->update(['reminder_pending' => false]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Attendance removed.']);
    }

    /* =====================================================================
     |  Register: month grid (players x practice days), replaces the Excel sheet
     * ===================================================================== */

    public function register(Request $request)
    {
        return view('attendance.register', $this->registerData($request) + [
            'groups' => AgeGroup::all(),
            'slots'  => PracticeSession::slots(),
        ]);
    }

    public function registerExport(Request $request)
    {
        $d    = $this->registerData($request);
        $mark = ['P' => 'P', 'E' => 'P (extra)', 'A' => 'AB', 'U' => 'AB (not counted)', 'T' => 'not marked yet', 'X' => 'No practice', 'N' => '-', 'F' => ''];

        $headings = ['Player No', 'Player', 'Group', 'Days'];
        foreach ($d['columns'] as $c) {
            $headings[] = $c['date']->format('D d M') . ($c['type'] === 'extra' ? ' (extra)' : '');
        }
        array_push($headings, 'Attended', 'Expected', 'Rate %');

        $rows = collect();
        foreach ($d['rows'] as $r) {
            $line = [$r['player']->player_no, $r['player']->name, $r['group'], $r['player']->days_text];
            foreach ($r['cells'] as $cell) {
                $line[] = $mark[$cell] ?? '';
            }
            array_push($line, $r['attended'], $r['expected'], $r['rate'] ?? '');
            $rows->push($line);
        }

        return Excel::download(new ReportExport($rows, $headings), 'attendance_register_' . $d['month'] . '.xlsx');
    }

    /**
     * Builds the grid. Cell codes:
     *  P = present, E = present on a day that is not theirs (extra), A = absent (used a session),
     *  U = absent but not counted (package already used up), T = today, not marked yet,
     *  X = practice cancelled, N = not their training day / not joined yet, F = still to come.
     */
    private function registerData(Request $request): array
    {
        $month = (string) $request->query('month');
        $m     = preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::createFromFormat('Y-m-d', $month . '-01') : today()->startOfMonth();
        $from  = $m->copy()->startOfMonth();
        $to    = $m->copy()->endOfMonth();
        $group = AgeGroup::exists($request->query('group')) ? $request->query('group') : null;
        $days  = $request->query('days');

        PracticeSession::generate($from, $to);

        $sessions = PracticeSession::whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('session_date')->orderBy('start_time')->get();

        $players = Player::active()
            ->inAgeGroup($group)
            ->trainingDays($days)
            ->get(['id', 'player_no', 'name', 'dob', 'photo_path', 'training_days', 'joined_at']);

        $order   = array_flip(AgeGroup::all());
        $players = $players->sortBy(fn ($p) => sprintf('%02d %s', $order[$p->age_group] ?? 99, strtolower($p->name)))->values();

        // [player_id][Y-m-d] = is_extra
        $marks = [];
        Attendance::whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('player_id', $players->pluck('id'))
            ->get(['player_id', 'attendance_date', 'is_extra'])
            ->each(function ($a) use (&$marks) {
                $marks[$a->player_id][$a->attendance_date->toDateString()] = (bool) $a->is_extra;
            });

        // [player_id][Y-m-d] = counted, written when the practice day was settled
        $absent = [];
        Absence::whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('player_id', $players->pluck('id'))
            ->get(['player_id', 'attendance_date', 'counted'])
            ->each(function ($a) use (&$absent) {
                $absent[$a->player_id][$a->attendance_date->toDateString()] = (bool) $a->counted;
            });

        // columns: every session of the month + days where someone was marked without a session (extra)
        $columns     = [];
        $activeDates = [];
        foreach ($sessions as $s) {
            $key = $s->session_date->toDateString();
            if (!$s->is_cancelled) {
                $activeDates[$key] = true;
            }
            $columns[] = [
                'key'     => $key,
                'date'    => $s->session_date,
                'type'    => $s->is_cancelled ? 'cancelled' : 'session',
                'slot'    => $s->slot,
                'moved'   => $s->is_moved,
                'session' => $s,
            ];
        }
        $extraDates = [];
        foreach ($marks as $byDate) {
            foreach (array_keys($byDate) as $date) {
                if (!isset($activeDates[$date])) {
                    $extraDates[$date] = true;
                }
            }
        }
        foreach (array_keys($extraDates) as $date) {
            $columns[] = ['key' => $date, 'date' => Carbon::parse($date), 'type' => 'extra', 'slot' => null, 'moved' => false, 'session' => null];
        }
        usort($columns, fn ($a, $b) => [$a['key'], $a['type'] === 'cancelled'] <=> [$b['key'], $b['type'] === 'cancelled']);

        $todayKey = today()->toDateString();
        $present  = array_fill(0, count($columns), 0);
        $rows     = [];

        foreach ($players as $p) {
            $cells    = [];
            $attended = 0;
            $expected = 0;
            $hit      = 0;

            foreach ($columns as $i => $c) {
                $isMarked = isset($marks[$p->id][$c['key']]);

                if ($isMarked) {
                    $ownDay = $c['type'] === 'session' && $p->trainsOn($c['slot']);
                    $cell   = $ownDay ? 'P' : 'E';
                    $attended++;
                    $present[$i]++;
                    if ($ownDay) {
                        $expected++;
                        $hit++;
                    }
                } elseif ($c['type'] === 'cancelled') {
                    $cell = 'X';
                } elseif ($c['type'] === 'extra' || !$p->trainsOn($c['slot'])
                    || ($p->joined_at && $p->joined_at->toDateString() > $c['key'])) {
                    $cell = 'N';
                } elseif ($c['key'] > $todayKey) {
                    $cell = 'F';
                } elseif ($c['key'] === $todayKey) {
                    $cell = 'T';
                } else {
                    $cell = ($absent[$p->id][$c['key']] ?? true) ? 'A' : 'U';
                    $expected++;
                }
                $cells[] = $cell;
            }

            $rows[] = [
                'player'   => $p,
                'group'    => $p->age_group,
                'cells'    => $cells,
                'attended' => $attended,
                'expected' => $expected,
                'rate'     => $expected > 0 ? (int) round($hit / $expected * 100) : null,
            ];
        }

        return [
            'month'   => $m->format('Y-m'),
            'monthTx' => $m->format('F Y'),
            'group'   => $group,
            'days'    => $days,
            'columns' => $columns,
            'rows'    => $rows,
            'present' => $present,
        ];
    }
}
