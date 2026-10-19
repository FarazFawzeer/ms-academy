<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public $timestamps = false;

    // age_group = the player's group on that day; is_extra = marked by an admin on a non-training day
    protected $fillable = ['player_id', 'age_group', 'attendance_date', 'method', 'is_extra', 'marked_by', 'created_at'];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at'      => 'datetime',
        'is_extra'        => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
