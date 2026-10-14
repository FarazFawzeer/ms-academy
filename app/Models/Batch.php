<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = ['name', 'schedule', 'coach_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
