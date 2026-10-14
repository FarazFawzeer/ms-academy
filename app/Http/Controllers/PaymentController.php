<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        $base = Payment::query()->whereBetween('paid_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $totals = (clone $base)->selectRaw('COUNT(*) as c, COALESCE(SUM(amount), 0) as s')->first();

        $payments = $base->with('player:id,name,player_no')
            ->latest('paid_at')->latest('id')
            ->paginate(20)->withQueryString();

        return view('payments.index', compact('payments', 'totals', 'from', 'to'));
    }

    public function store(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), [
            'amount'   => 'required|numeric|min:0|max:1000000',
            'sessions' => 'required|integer|min:1|max:64',
            'method'   => 'required|in:cash,bank,card,online',
            'note'     => 'nullable|string|max:150',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        [, $balance] = Payment::record(
            $player->id,
            (float) $request->input('amount'),
            (int) $request->input('sessions'),
            $request->input('method'),
            $request->input('note'),
            $request->user()?->id
        );

        $player->sessions_balance = $balance;

        return response()->json([
            'success' => true,
            'message' => 'Payment received. ' . $player->name . ' now has ' . $balance . ' session(s).',
            'balance' => $balance,
            'badge'   => (string) $player->balanceBadge(),
        ]);
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            Player::whereKey($payment->player_id)->decrement('sessions_balance', $payment->sessions);
            $payment->delete();
        });

        return response()->json(['success' => true, 'message' => 'Payment removed and sessions deducted.']);
    }
}
