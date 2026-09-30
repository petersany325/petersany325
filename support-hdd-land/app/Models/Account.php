<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'nature', 'is_system', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public const TYPES = [
        'asset' => 'دارایی',
        'liability' => 'بدهی',
        'equity' => 'حقوق مالکانه',
        'income' => 'درآمد',
        'expense' => 'هزینه',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public static function byCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }
}
