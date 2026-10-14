<?php

namespace App\Http\Controllers;

use App\Models\Player;

class ReminderController extends Controller
{
    public function index()
    {
        $cols = ['id', 'player_no', 'name', 'photo_path', 'batch_id', 'guardian_name', 'guardian_phone',
            'sessions_balance', 'last_reminded_at'];

        $pending = Player::active()->where('reminder_pending', true)
            ->with('batch:id,name')->orderBy('name')->get($cols);

        $recent = Player::whereNotNull('last_reminded_at')
            ->orderByDesc('last_reminded_at')->limit(10)->get(['id', 'player_no', 'name', 'guardian_name', 'last_reminded_at']);

        return view('reminders.index', compact('pending', 'recent'));
    }

    /** Called after the WhatsApp link is opened. */
    public function sent(Player $player)
    {
        $player->update(['reminder_pending' => false, 'last_reminded_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Marked as sent.']);
    }

    /** Remove from the list without sending. */
    public function dismiss(Player $player)
    {
        $player->update(['reminder_pending' => false]);

        return response()->json(['success' => true, 'message' => 'Reminder dismissed.']);
    }
}
