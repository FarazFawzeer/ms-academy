<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Player;
use App\Support\PlayerSessions;
use Carbon\Carbon;
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
            'paid_on'  => 'nullable|date|before_or_equal:today',
        ], [
            'paid_on.before_or_equal' => 'The payment date cannot be in the future.',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        // Admin may enter an older payment (history from before the system); others always "now"
        $paidAt = null;
        $isAdmin = in_array($request->user()?->type, ['Super Admin', 'Admin'], true);
        if ($isAdmin && $request->filled('paid_on') && Carbon::parse($request->input('paid_on'))->toDateString() !== today()->toDateString()) {
            $paidAt = Carbon::parse($request->input('paid_on'))->setTime(12, 0);
        }

        [$payment, $balance] = Payment::record(
            $player->id,
            (float) $request->input('amount'),
            (int) $request->input('sessions'),
            $request->input('method'),
            $request->input('note'),
            $request->user()?->id,
            $paidAt
        );

        $player->sessions_balance = $balance;

        return response()->json([
            'success'  => true,
            'message'  => 'Payment received. ' . $player->name . ' now has ' . $balance . ' session(s).',
            'balance'  => $balance,
            'badge'    => (string) $player->balanceBadge(),
            'undo_url' => route('admin.payments.undo', $payment),
            'receipt_url' => route('admin.payments.receipt', $payment),
        ]);
    }

    /** Printable receipt (client, 3 Nov 2026), like the academy's paper invoice. Print or save as PDF. */
    public function receipt(Payment $payment)
    {
        $payment->load('player', 'receiver');
        $player = $payment->player;
        $plan   = $player ? $player->plan() : Player::planFor(null);
        $perWeek = max(1, $player ? $player->remindThreshold() : 2);   // practices per week

        $isExtra = $payment->method === 'extra';
        $total   = $isExtra ? 0.0 : max((float) $plan['fee'], (float) $payment->amount);
        $paid    = (float) $payment->amount;

        $wa = null;
        if ($player && $player->whatsapp_number) {
            $text = config('academy.name') . ' - Payment receipt ' . $payment->receipt_no . "\n"
                . $player->name . ' (' . $player->player_no . ")\n"
                . ($isExtra ? 'Free extra days: ' . $payment->sessions : 'Paid: LKR ' . number_format($paid) . ' (' . ucfirst($payment->method) . ')')
                . ' on ' . $payment->paid_at->format('d M Y') . "\n"
                . 'Sessions: ' . $payment->sessions . ' (' . ceil($payment->sessions / $perWeek) . " weeks)\nThank you!";
            $wa = 'https://wa.me/' . $player->whatsapp_number . '?text=' . rawurlencode($text);
        }

        // the practice days this payment pays for, grouped by week (Fri + Sun = 2 a week)
        $dates = $isExtra ? [] : PlayerSessions::paymentDates($payment);
        $weekRows = array_chunk($dates, $perWeek);

        return view('payments.receipt', [
            'weekRows' => $weekRows,
            'payment' => $payment,
            'player'  => $player,
            'plan'    => $plan,
            'weeks'   => (int) ceil($payment->sessions / $perWeek),
            'perWeek' => $perWeek,
            'isExtra' => $isExtra,
            'total'   => $total,
            'paid'    => $paid,
            'balance' => max(0, $total - $paid),
            'wa'      => $wa,
        ]);
    }

    /**
     * Admin only: give a player extra days for free (e.g. came on the first and the last day of the package).
     * Saved as a LKR 0 payment with method "extra" so it shows in the payment history with the reason.
     */
    public function extraDays(Request $request, Player $player)
    {
        $v = Validator::make($request->all(), [
            'days'   => 'required|integer|min:1|max:16',
            'reason' => 'required|string|max:120',
        ], [
            'reason.required' => 'Write a short reason, e.g. "Missed 3 weeks - injury".',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $days = (int) $request->input('days');

        [, $balance] = Payment::record(
            $player->id,
            0,
            $days,
            'extra',
            'Extra days: ' . trim($request->input('reason')),
            $request->user()?->id
        );

        $player->sessions_balance = $balance;

        return response()->json([
            'success' => true,
            'message' => $days . ' extra day(s) given. ' . $player->name . ' now has ' . $balance . ' session(s).',
            'balance' => $balance,
            'badge'   => (string) $player->balanceBadge(),
        ]);
    }

    /** Undo a payment entered by mistake (Admin: any; others: their own, same day). */
    public function undo(Request $request, Payment $payment)
    {
        if (!$payment->canBeUndoneBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only an Admin can undo this payment (it was recorded by someone else or on an earlier day).',
            ], 403);
        }

        return $this->reverse($payment);
    }

    /** Admin only (older route, same result as undo). */
    public function destroy(Payment $payment)
    {
        return $this->reverse($payment);
    }

    /** Remove the payment and take its sessions back off; the renewal reminder follows the new balance. */
    private function reverse(Payment $payment)
    {
        $player = Player::find($payment->player_id);
        $what   = $payment->method === 'extra'
            ? $payment->sessions . ' extra day(s)'
            : 'Payment of LKR ' . number_format((float) $payment->amount);

        $balance = DB::transaction(function () use ($payment, $player) {
            Player::whereKey($payment->player_id)->decrement('sessions_balance', $payment->sessions);
            $payment->delete();

            return $player ? PlayerSessions::refreshReminder($player, true) : 0;
        });

        if ($player) {
            $player->sessions_balance = $balance;
        }

        return response()->json([
            'success'   => true,
            'message'   => $what . ' undone.' . ($player ? ' ' . $player->name . ' now has ' . $balance . ' session(s).' : ''),
            'player_id' => $payment->player_id,
            'balance'   => $balance,
            'badge'     => $player ? (string) $player->balanceBadge() : '',
        ]);
    }
}
