<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code', 'name', 'location', 'is_default', 'is_active', 'note',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function defaultId(): ?int
    {
        return static::query()->where('is_default', true)->where('is_active', true)->value('id')
            ?: static::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    public function makeDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }
}
