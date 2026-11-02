<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceTier extends Model
{
    protected $table = 'price_tiers';

    protected $fillable = [
        'name', 'code', 'sort_order', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function partPrices(): HasMany
    {
        return $this->hasMany(PartPrice::class);
    }

    public static function ensureDefaults(): void
    {
        if (static::query()->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'عمومی', 'code' => 'public', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'همکار', 'code' => 'partner', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'خاص', 'code' => 'vip', 'sort_order' => 3, 'is_default' => false],
        ];

        foreach ($defaults as $row) {
            static::create($row + ['is_active' => true]);
        }
    }
}
