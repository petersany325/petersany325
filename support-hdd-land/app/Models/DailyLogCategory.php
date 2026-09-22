<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyLogCategory extends Model
{
    protected $fillable = [
        'name', 'hint', 'mark', 'sort_order', 'ask_quantity', 'requires_receipt', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ask_quantity' => 'boolean',
            'requires_receipt' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DailyLogEntry::class);
    }

    /** Categories that should open receipt search when selected. */
    public function needsReceipt(): bool
    {
        if ($this->requires_receipt) {
            return true;
        }

        $name = (string) $this->name;

        return str_contains($name, 'قبض')
            || str_contains($name, 'تعمیر')
            || str_contains($name, 'قطعه');
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
