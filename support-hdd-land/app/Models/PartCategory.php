<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartCategory extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'item_type', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const ITEM_TYPES = [
        'shop' => 'اجناس فروشگاه',
        'repair' => 'قطعات تعمیرگاه',
        'labor' => 'اجرت / خدمات',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class, 'category_id');
    }

    public function itemTypeLabel(): string
    {
        return self::ITEM_TYPES[$this->item_type] ?? $this->item_type;
    }

    /** Flat indented options for selects. */
    public static function optionsTree(?int $excludeId = null): array
    {
        $all = static::query()->orderBy('sort_order')->orderBy('name')->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?: 0);
        $out = [];
        $walk = function ($parentId, $depth) use (&$walk, &$out, $byParent, $excludeId) {
            foreach ($byParent[$parentId] ?? [] as $node) {
                if ($excludeId && (int) $node->id === (int) $excludeId) {
                    continue;
                }
                $out[$node->id] = str_repeat('— ', $depth).$node->name.' ('.$node->itemTypeLabel().')';
                $walk($node->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }
}
