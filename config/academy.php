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
];
