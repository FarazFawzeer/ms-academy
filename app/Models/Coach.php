<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends Model
{
    protected $fillable = ['name', 'phone', 'nic', 'photo_path', 'daily_rate', 'is_active'];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public function workdays(): HasMany
    {
        return $this->hasMany(CoachWorkday::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path ? asset($this->photo_path) : asset('images/users/avatar-6.jpg');
    }
}
