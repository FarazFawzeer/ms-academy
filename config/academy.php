<?php

return [

    'name' => 'MS Football Academy',

    // Fee rules - change here, nothing else needs touching
    'fee'                  => 3500,   // LKR per package
    'sessions_per_package' => 8,      // sessions the fee covers
    'remind_at_session'    => 6,      // reminder is queued when this session is completed

    // Player number prefix: MS-0001, MS-0002 ...
    'player_prefix' => 'MS',

    // Placeholders: {guardian} {player} {used} {total} {fee} {academy}
    'whatsapp_message' => "Hi {guardian}, {player} has completed {used} of {total} sessions at {academy}. "
        . "Please renew for the next {total} sessions (LKR {fee}). Thank you!",

    /*
    | Age groups (automatic, from date of birth and today's date).
    | Name => highest age in the group. "Under 12" = age 11 or below.
    | Keep them from youngest to oldest. Anyone older than the last group is "Senior".
    */
    'age_groups' => [
        'U8'  => 7,
        'U10' => 9,
        'U12' => 11,
        'U14' => 13,
        'U16' => 15,
        'U18' => 17,
    ],
    'senior_group' => 'Senior',

    /*
    | Regular weekly practice days. Sessions for these days are created automatically;
    | the admin can move, re-time or cancel any single session on the Practice Schedule page.
    | weekday: 0 = Sunday ... 6 = Saturday. Times are the defaults for new sessions.
    */
    'training_days' => [
        'fri' => ['label' => 'Friday', 'short' => 'Fri', 'weekday' => 5, 'start' => '16:00', 'end' => '17:30'],
        'sun' => ['label' => 'Sunday', 'short' => 'Sun', 'weekday' => 0, 'start' => '16:00', 'end' => '17:30'],
    ],

    // No sessions are created before this date (the day the academy started using the system)
    'schedule_start' => env('ACADEMY_SCHEDULE_START', '2026-10-01'),

    // How far ahead the schedule is filled
    'schedule_weeks_ahead' => 8,
];
