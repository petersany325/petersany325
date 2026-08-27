<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyLogCategory extends Model
{
    protected $fillable = [
        'name', 'hint', 'mark', 'sort_order', 'ask_quantity', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ask_quantity' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DailyLogEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
