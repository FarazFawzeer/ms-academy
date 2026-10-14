<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Player extends Model
{
    protected $fillable = [
        'player_no', 'qr_token', 'name', 'dob', 'phone', 'guardian_name', 'guardian_phone',
        'photo_path', 'batch_id', 'joined_at', 'sessions_balance', 'reminder_pending',
        'last_reminded_at', 'is_active',
    ];

    protected $casts = [
        'dob'              => 'date',
        'joined_at'        => 'date',
        'sessions_balance' => 'integer',
        'reminder_pending' => 'boolean',
        'is_active'        => 'boolean',
        'last_reminded_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /* ---------- scopes ---------- */

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';

        return $q->where(function ($w) use ($like, $term) {
            $w->where('name', 'like', $like)
              ->orWhere('player_no', 'like', $term . '%')
              ->orWhere('guardian_phone', 'like', $like);
        });
    }

    /* ---------- fee / session rules ---------- */

    public static function perPackage(): int
    {
        return (int) config('academy.sessions_per_package', 8);
    }

    /** Sessions left at which the renewal reminder is queued (8 - 6 = 2). */
    public static function remindThreshold(): int
    {
        return max(0, self::perPackage() - (int) config('academy.remind_at_session', 6));
    }

    /** "Session N of 8" number for a balance AFTER an attendance was counted. */
    public static function sessionNumber(int $balance): int
    {
        $n = self::perPackage();
        if ($balance < 0) {
            return $n;
        }
        $r = $balance % $n;

        return $r === 0 ? $n : $n - $r;
    }

    /* ---------- accessors / helpers ---------- */

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path ? asset($this->photo_path) : asset('images/users/avatar-6.jpg');
    }

    public function getAgeAttribute(): ?int
    {
        return $this->dob ? $this->dob->age : null;
    }

    /** Sri Lankan number -> international digits for wa.me (0771234567 -> 94771234567). */
    public function getWhatsappNumberAttribute(): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $this->guardian_phone);
        if ($d === '') {
            return null;
        }
        if (str_starts_with($d, '94') && strlen($d) === 11) {
            return $d;
        }
        if (str_starts_with($d, '0') && strlen($d) === 10) {
            return '94' . substr($d, 1);
        }
        if (strlen($d) === 9) {
            return '94' . $d;
        }

        return $d;
    }

    public function balanceBadge(): HtmlString
    {
        $b = (int) $this->sessions_balance;

        // Soft fixed colours, plain ASCII text only (no special characters)
        if ($b < 0) {
            [$c, $txt] = ['#b91c1c', 'Due: ' . abs($b) . ' unpaid'];
        } elseif ($b === 0) {
            [$c, $txt] = ['#b45309', 'Renew now'];
        } elseif ($b <= self::remindThreshold()) {
            [$c, $txt] = ['#b45309', $b . ' left'];
        } else {
            [$c, $txt] = ['#15803d', $b . ' left'];
        }

        return new HtmlString(
            '<span class="badge" style="background:' . $c . '1a;color:' . $c
            . ';display:inline-block;width:110px;text-align:center;font-weight:600;font-size:12px;'
            . 'padding:6px 0;border-radius:6px;white-space:nowrap">' . e($txt) . '</span>'
        );
    }

    public function reminderMessage(): string
    {
        $used = self::sessionNumber((int) $this->sessions_balance);

        return strtr(config('academy.whatsapp_message'), [
            '{guardian}' => $this->guardian_name,
            '{player}'   => $this->name,
            '{used}'     => $used,
            '{total}'    => self::perPackage(),
            '{fee}'      => number_format((float) config('academy.fee')),
            '{academy}'  => config('academy.name'),
        ]);
    }

    public function whatsappLink(): ?string
    {
        $no = $this->whatsapp_number;

        return $no ? 'https://wa.me/' . $no . '?text=' . rawurlencode($this->reminderMessage()) : null;
    }
}