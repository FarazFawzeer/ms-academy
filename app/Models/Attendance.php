<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public $timestamps = false;

    protected $fillable = ['player_id', 'batch_id', 'attendance_date', 'method', 'marked_by', 'created_at'];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at'      => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
