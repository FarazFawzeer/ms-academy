<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A practice day the player missed. counted = it used one of their paid sessions. */
class Absence extends Model
{
    public $timestamps = false;

    protected $fillable = ['player_id', 'age_group', 'attendance_date', 'counted', 'created_at'];

    protected $casts = [
        'attendance_date' => 'date',
        'counted'         => 'boolean',
        'created_at'      => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
