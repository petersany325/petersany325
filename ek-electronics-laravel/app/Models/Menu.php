<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'parent_id', 'location', 'label', 'url', 'hint', 'target', 'sort_order', 'is_active', 'is_highlighted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Menu $menu): void {
            if ($menu->parent_id && (int) $menu->parent_id === (int) $menu->id) {
                $menu->parent_id = null;
            }

            if (! $menu->parent_id) {
                return;
            }

            $parent = static::query()->find($menu->parent_id);
            if (! $parent) {
                $menu->parent_id = null;

                return;
            }

            // One level of submenus. A child always follows its parent location.
            $menu->parent_id = $parent->parent_id ?: $parent->id;
            $menu->location = $parent->location;
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }
}
