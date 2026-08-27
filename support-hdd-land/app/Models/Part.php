<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    protected $fillable = [
        'warehouse_id', 'code', 'name', 'brand', 'model', 'stock',
        'purchase_price', 'sale_price', 'min_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
}
