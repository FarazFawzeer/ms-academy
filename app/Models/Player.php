<?php

namespace App\Models;

use App\Support\AgeGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;

class Player extends Model
{
    protected $fillable = [
        'player_no', 'qr_token', 'name', 'dob', 'phone', 'guardian_name', 'guardian_phone',
        'training_days', 'photo_path', 'joined_at', 'sessions_balance', 'reminder_pending',
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
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

    /** Players whose age TODAY puts them in this group (U8, U10 ... Senior). */
    public function scopeInAgeGroup($q, ?string $label)
    {
        if (!AgeGroup::exists($label)) {
            return $q;
        }
        [$after, $onOrBefore] = AgeGroup::dobRange($label);

        return $q->when($after, fn ($w) => $w->where('dob', '>', $after))
                 ->when($onOrBefore, fn ($w) => $w->where('dob', '<=', $onOrBefore));
    }

    /** 'fri' = trains on Friday (both-days players included), 'fri-only', 'sun-only', 'both'. */
    public function scopeTrainingDays($q, ?string $filter)
    {
        $all = implode(',', array_keys(PracticeSession::slots()));

        return match (true) {
            $filter === 'both'                    => $q->where('training_days', $all),
            str_ends_with((string) $filter, '-only') => $q->where('training_days', substr($filter, 0, -5)),
            isset(PracticeSession::slots()[$filter]) => $q->where('training_days', 'like', '%' . $filter . '%'),
            default                               => $q,
        };
    }

    /* ---------- fee plan / session rules ---------- */

    /** Plan for a training-days value: ['label' => 'Friday only', 'fee' => 2500, 'sessions' => 4]. */
    public static function planFor(?string $days): array
    {
        $plans = (array) config('academy.plans', []);

        return $plans[$days] ?? $plans[implode(',', array_keys(PracticeSession::slots()))] ?? ['label' => '-', 'fee' => 0, 'sessions' => 8];
    }

    public function plan(): array
    {
        return self::planFor($this->training_days);
    }

    public function planSessions(): int
    {
        return (int) $this->plan()['sessions'];
    }

    public function planFee(): float
    {
        return (float) $this->plan()['fee'];
    }

    /** Sessions left at which the renewal reminder is queued: one week of practice (2 for Fri+Sun, 1 for one day). */
    public function remindThreshold(): int
    {
        return max(1, count($this->days));
    }

    /** "Session N of 8" number for a balance AFTER a session was used. */
    public function sessionNumber(int $balance): int
    {
        $n = $this->planSessions();
        if ($balance < 0) {
            return $n;
        }
        $r = $balance % $n;

        return $r === 0 ? $n : $n - $r;
    }

    /* ---------- training days ---------- */

    /** Sorted, valid slot keys from a form value (['sun','fri'] -> 'fri,sun'). */
    public static function normaliseDays($days): string
    {
        $days = array_map('strval', (array) $days);

        return implode(',', array_values(array_filter(array_keys(PracticeSession::slots()), fn ($k) => in_array($k, $days, true))));
    }

    /** ['fri', 'sun'] */
    public function getDaysAttribute(): array
    {
        return array_values(array_filter(explode(',', (string) $this->training_days)));
    }

    public function trainsOn(?string $slot): bool
    {
        return $slot !== null && in_array($slot, $this->days, true);
    }

    /** "Fri & Sun", "Friday only" */
    public function getDaysTextAttribute(): string
    {
        $slots = PracticeSession::slots();
        $days  = $this->days;

        if (count($days) === 1) {
            return ($slots[$days[0]]['label'] ?? $days[0]) . ' only';
        }

        return implode(' & ', array_map(fn ($d) => $slots[$d]['short'] ?? $d, $days)) ?: '-';
    }

    public function daysBadge(): HtmlString
    {
        $c = count($this->days) > 1 ? '#0f766e' : '#2563eb';

        return self::softBadge($c, $this->days_text, 110);
    }

    /* ---------- age group (always from today's age) ---------- */

    public function getAgeGroupAttribute(): ?string
    {
        return AgeGroup::forDob($this->dob);
    }

    public function ageGroupBadge(): HtmlString
    {
        $g = $this->age_group;

        return self::softBadge(AgeGroup::colour($g), $g ?? '-', 64);
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
        } elseif ($b <= $this->remindThreshold()) {
            [$c, $txt] = ['#b45309', $b . ' left'];
        } else {
            [$c, $txt] = ['#15803d', $b . ' left'];
        }

        return self::softBadge($c, $txt, 110);
    }

    /** Shared soft-tint badge: light background, coloured text, fixed width. */
    public static function softBadge(string $colour, string $text, int $width = 110): HtmlString
    {
        return new HtmlString(
            '<span class="badge" style="background:' . $colour . '1a;color:' . $colour
            . ';display:inline-block;width:' . $width . 'px;text-align:center;font-weight:600;font-size:12px;'
            . 'padding:6px 0;border-radius:6px;white-space:nowrap">' . e($text) . '</span>'
        );
    }

    public function reminderMessage(): string
    {
        $used = $this->sessionNumber((int) $this->sessions_balance);

        return strtr(config('academy.whatsapp_message'), [
            '{guardian}' => $this->guardian_name,
            '{player}'   => $this->name,
            '{used}'     => $used,
            '{total}'    => $this->planSessions(),
            '{fee}'      => number_format($this->planFee()),
            '{academy}'  => config('academy.name'),
        ]);
    }

    public function whatsappLink(): ?string
    {
        $no = $this->whatsapp_number;

        return $no ? 'https://wa.me/' . $no . '?text=' . rawurlencode($this->reminderMessage()) : null;
    }
}
