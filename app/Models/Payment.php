<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $fillable = ['player_id', 'amount', 'sessions', 'method', 'note', 'paid_at', 'received_by'];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Undo (mistakes happen at the desk): an Admin can undo any payment; anyone else only a payment
     * they recorded themselves, on the same day.
     */
    public function canBeUndoneBy($user): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->type, ['Super Admin', 'Admin'], true)) {
            return true;
        }

        return (int) $this->received_by === (int) $user->id && $this->created_at && $this->created_at->isToday();
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Record a payment and add the paid sessions to the player's balance in one transaction.
     * Returns [Payment, newBalance].
     */
    public static function record(int $playerId, float $amount, int $sessions, string $method = 'cash', ?string $note = null, ?int $userId = null): array
    {
        return DB::transaction(function () use ($playerId, $amount, $sessions, $method, $note, $userId) {
            $payment = static::create([
                'player_id'   => $playerId,
                'amount'      => $amount,
                'sessions'    => $sessions,
                'method'      => $method,
                'note'        => $note,
                'paid_at'     => now(),
                'received_by' => $userId,
            ]);

            // a renewal clears any pending reminder
            Player::whereKey($playerId)->increment('sessions_balance', $sessions, ['reminder_pending' => false]);
            $balance = (int) Player::whereKey($playerId)->value('sessions_balance');

            return [$payment, $balance];
        });
    }
}
