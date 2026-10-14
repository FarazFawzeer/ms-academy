<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Player;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /** Scan + search + today's list on one screen. */
    public function scan()
    {
        $today = Attendance::query()
            ->join('players', 'players.id', '=', 'attendances.player_id')
            ->leftJoin('batches', 'batches.id', '=', 'attendances.batch_id')
            ->where('attendances.attendance_date', today()->toDateString())
            ->orderByDesc('attendances.id')
            ->get([
                'attendances.id', 'attendances.created_at', 'attendances.method',
                'players.name', 'players.player_no', 'players.photo_path',
                'batches.name as batch_name',
            ]);

        return view('attendance.scan', compact('today'));
    }

    /** Mark by QR token (scanner) or player_id (manual search). One query to find, one transaction to write. */
    public function mark(Request $request)
    {
        $token = strtoupper(trim((string) $request->input('token')));
        $cols  = ['id', 'player_no', 'name', 'photo_path', 'batch_id', 'sessions_balance', 'is_active'];

        $player = $token !== ''
            ? Player::where('qr_token', $token)->first($cols)
            : Player::find((int) $request->input('player_id'), $cols);

        if (!$player) {
            return response()->json(['success' => false, 'status' => 'not_found', 'message' => 'QR code not recognised.'], 404);
        }

        $info = [
            'id'    => $player->id,
            'name'  => $player->name,
            'no'    => $player->player_no,
            'photo' => $player->photo_url,
            'batch' => $player->batch_id ? Batch::whereKey($player->batch_id)->value('name') : null,
        ];

        if (!$player->is_active) {
            return response()->json(['success' => false, 'status' => 'inactive', 'message' => 'This player is inactive.', 'player' => $info], 422);
        }

        $today = today()->toDateString();

        try {
            [$attendanceId, $balance, $remind] = DB::transaction(function () use ($player, $today, $request, $token) {
                // the UNIQUE(player_id, attendance_date) index rejects a double scan, even from two phones at once
                $id = DB::table('attendances')->insertGetId([
                    'player_id'       => $player->id,
                    'batch_id'        => $player->batch_id,
                    'attendance_date' => $today,
                    'method'          => $token !== '' ? 'qr' : 'manual',
                    'marked_by'       => $request->user()?->id,
                    'created_at'      => now(),
                ]);

                DB::table('players')->where('id', $player->id)->decrement('sessions_balance');
                $balance = (int) DB::table('players')->where('id', $player->id)->value('sessions_balance');

                $remind = $balance === Player::remindThreshold();
                if ($remind) {
                    DB::table('players')->where('id', $player->id)->update(['reminder_pending' => true]);
                }

                return [$id, $balance, $remind];
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? '') !== '23000') {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'status'  => 'duplicate',
                'message' => $player->name . ' is already marked present today.',
                'player'  => $info,
            ]);
        }

        return response()->json([
            'success'       => true,
            'status'        => 'marked',
            'message'       => 'Attendance marked.',
            'player'        => $info,
            'attendance_id' => $attendanceId,
            'session'       => $balance < 0 ? null : Player::sessionNumber($balance),
            'total'         => Player::perPackage(),
            'balance'       => $balance,
            'payment_due'   => $balance < 0,
            'reminder'      => $remind,
            'time'          => now()->format('h:i A'),
        ]);
    }

    /** Quick manual search used on the scan screen. */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = Player::active()
            ->search($q)
            ->with('batch:id,name')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'player_no', 'name', 'photo_path', 'batch_id', 'sessions_balance']);

        return response()->json($rows->map(fn (Player $p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'no'    => $p->player_no,
            'photo' => $p->photo_url,
            'batch' => $p->batch?->name,
            'badge' => (string) $p->balanceBadge(),
        ]));
    }

    /** Undo a wrong scan (today only). Gives the session back. */
    public function destroy(Attendance $attendance)
    {
        if (!$attendance->attendance_date->isToday()) {
            return response()->json(['success' => false, 'message' => 'Only today\'s attendance can be undone.'], 422);
        }

        DB::transaction(function () use ($attendance) {
            DB::table('attendances')->where('id', $attendance->id)->delete();
            DB::table('players')->where('id', $attendance->player_id)->increment('sessions_balance');

            $balance = (int) DB::table('players')->where('id', $attendance->player_id)->value('sessions_balance');
            if ($balance > Player::remindThreshold()) {
                DB::table('players')->where('id', $attendance->player_id)->update(['reminder_pending' => false]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Attendance removed.']);
    }

    /** Look at any past day, optionally per batch. */
    public function history(Request $request)
    {
        $date    = $request->query('date', today()->toDateString());
        $batchId = $request->query('batch_id');

        $rows = Attendance::query()
            ->join('players', 'players.id', '=', 'attendances.player_id')
            ->leftJoin('batches', 'batches.id', '=', 'attendances.batch_id')
            ->where('attendances.attendance_date', $date)
            ->when($batchId, fn ($q) => $q->where('attendances.batch_id', $batchId))
            ->orderBy('players.name')
            ->get(['attendances.created_at', 'attendances.method', 'players.id as player_id', 'players.name',
                'players.player_no', 'players.photo_path', 'batches.name as batch_name']);

        $batches = Batch::orderBy('name')->get(['id', 'name']);

        return view('attendance.history', compact('rows', 'batches', 'date', 'batchId'));
    }
}
