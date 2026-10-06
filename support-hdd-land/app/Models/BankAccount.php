<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'name', 'bank_name', 'account_number', 'iban', 'card_number',
        'account_type', 'gl_code', 'is_default', 'is_active', 'sort_order', 'note',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fixedCosts(): HasMany
    {
        return $this->hasMany(FixedCost::class);
    }

    public function typeLabel(): string
    {
        return match ($this->account_type) {
            'cash' => 'صندوق نقد',
            'card' => 'کارتخوان',
            'transfer' => 'کارت‌به‌کارت / شبا',
            default => $this->account_type,
        };
    }

    public function resolvedGlCode(): string
    {
        if ($this->gl_code) {
            return $this->gl_code;
        }

        return match ($this->account_type) {
            'cash' => '1110',
            'card' => '1120',
            default => '1130',
        };
    }

    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
    }
}
