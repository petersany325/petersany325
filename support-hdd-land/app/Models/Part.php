<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    protected $fillable = [
        'warehouse_id', 'category_id', 'item_type', 'code', 'tech_code', 'barcode', 'name', 'brand', 'model',
        'keywords', 'description', 'stock',
        'purchase_price', 'sale_price', 'discount_percent', 'sale_commission_percent', 'repair_commission_percent',
        'min_stock', 'usage_count', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const ITEM_TYPES = PartCategory::ITEM_TYPES;

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartCategory::class, 'category_id');
    }

    public function tierPrices(): HasMany
    {
        return $this->hasMany(PartPrice::class);
    }

    public function receptionParts(): HasMany
    {
        return $this->hasMany(ReceptionPart::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function isOutOfStock(): bool
    {
        return (int) $this->stock <= 0;
    }

    public function itemTypeLabel(): string
    {
        return self::ITEM_TYPES[$this->item_type] ?? ($this->item_type ?: '—');
    }

    public function barcodeValue(): string
    {
        $b = trim((string) $this->barcode);
        if ($b !== '') {
            return $b;
        }
        $c = trim((string) $this->code);
        if ($c !== '') {
            return $c;
        }

        return 'P'.$this->id;
    }

    public function priceForTier(?int $tierId = null): int
    {
        if ($tierId) {
            $row = $this->tierPrices()->where('price_tier_id', $tierId)->first();
            if ($row) {
                return (int) $row->price;
            }
        }

        return (int) $this->sale_price;
    }

    public static function bumpUsage(int $partId, int $by = 1): void
    {
        static::query()->where('id', $partId)->increment('usage_count', $by);
    }
}
