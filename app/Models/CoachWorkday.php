<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachWorkday extends Model
{
    public $timestamps = false;

    protected $fillable = ['coach_id', 'work_date', 'amount', 'paid_at'];

    protected $casts = [
        'work_date' => 'date',
        'amount'    => 'decimal:2',
        'paid_at'   => 'datetime',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
