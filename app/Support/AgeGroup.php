<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Age groups are never stored on the player: they are worked out from the date of birth
 * and today's date, so a player moves to the next group on their birthday automatically.
 * Bands live in config/academy.php ('age_groups' and 'senior_group').
 */
class AgeGroup
{
    /** ['U8' => 7, 'U10' => 9, ...] youngest first */
    public static function bands(): array
    {
        $bands = (array) config('academy.age_groups', []);
        asort($bands);

        return $bands;
    }

    public static function senior(): string
    {
        return (string) config('academy.senior_group', 'Senior');
    }

    /** Every group name, youngest first, Senior last. */
    public static function all(): array
    {
        return [...array_keys(self::bands()), self::senior()];
    }

    public static function exists(?string $label): bool
    {
        return $label !== null && in_array($label, self::all(), true);
    }

    public static function forAge(?int $age): ?string
    {
        if ($age === null) {
            return null;
        }
        foreach (self::bands() as $label => $max) {
            if ($age <= $max) {
                return $label;
            }
        }

        return self::senior();
    }

    public static function forDob($dob, $on = null): ?string
    {
        if (!$dob) {
            return null;
        }
        $on = $on ? Carbon::parse($on) : today();

        return self::forAge((int) Carbon::parse($dob)->diffInYears($on));
    }

    /** [minAge, maxAge|null] for a group. */
    public static function ages(string $label): array
    {
        $min = 0;
        foreach (self::bands() as $name => $max) {
            if ($name === $label) {
                return [$min, $max];
            }
            $min = $max + 1;
        }

        return [$min, null]; // Senior
    }

    /** "Age 10 - 11", "Age 7 and below", "Age 18 and above" */
    public static function rangeText(string $label): string
    {
        [$min, $max] = self::ages($label);

        if ($max === null) {
            return 'Age ' . $min . ' and above';
        }

        return $min === 0 ? 'Age ' . $max . ' and below' : 'Age ' . $min . ' - ' . $max;
    }

    /**
     * Date-of-birth limits for a WHERE clause: [bornAfter|null, bornOnOrBefore|null].
     * Age <= max  <=>  dob > today - (max + 1) years.   Age >= min  <=>  dob <= today - min years.
     */
    public static function dobRange(string $label): array
    {
        [$min, $max] = self::ages($label);
        $today = today();

        return [
            $max === null ? null : $today->copy()->subYears($max + 1)->toDateString(),
            $min === 0 ? null : $today->copy()->subYears($min)->toDateString(),
        ];
    }

    /** The day a player of this DOB moves to the next group (null for Senior). */
    public static function nextMove($dob): ?Carbon
    {
        if (!$dob) {
            return null;
        }
        $dob   = Carbon::parse($dob);
        $group = self::forDob($dob);
        [, $max] = self::ages($group);

        return $max === null ? null : $dob->copy()->addYears($max + 1);
    }

    /** Soft fixed colour per group (same look as the other badges). */
    public static function colour(?string $label): string
    {
        $palette = ['#0f766e', '#2563eb', '#7c3aed', '#c2410c', '#be185d', '#4d7c0f', '#0369a1', '#475569'];
        $i = array_search($label, self::all(), true);

        return $i === false ? '#6b7280' : $palette[$i % count($palette)];
    }
}
