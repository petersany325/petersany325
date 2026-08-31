<?php

namespace Plugins\Catalog\src\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'is_active', 'sort_order',
        'image', 'banner_image', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name) ?: 'cat-'.Str::random(6);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function fullName(): string
    {
        if ($this->parent) {
            return $this->parent->name.' ← '.$this->name;
        }

        return $this->name;
    }

    public function imageUrl(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) ($this->image ?? '')), '/');
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (is_file(public_path('uploads/'.$path))) {
            return asset('uploads/'.$path);
        }

        return asset('storage/'.$path);
    }

    public function bannerUrl(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) ($this->banner_image ?? '')), '/');
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (is_file(public_path('uploads/'.$path))) {
            return asset('uploads/'.$path);
        }

        return asset('storage/'.$path);
    }

    /** @return array<string,mixed> */
    public function settingBag(): array
    {
        return array_merge([
            'display_type' => 'default',
            'seo_title' => '',
            'seo_description' => '',
            'menu_label' => '',
            'show_in_menu' => true,
        ], is_array($this->settings) ? $this->settings : []);
    }

    /** Nested tree for admin drag-drop UI */
    public static function nestedTree(): array
    {
        $all = static::query()
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $byParent = $all->groupBy(fn (self $c) => (int) ($c->parent_id ?? 0));

        $build = function (int $parentId) use (&$build, $byParent): array {
            return ($byParent[$parentId] ?? collect())->map(function (self $c) use (&$build) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'parent_id' => $c->parent_id,
                    'sort_order' => (int) $c->sort_order,
                    'is_active' => (bool) $c->is_active,
                    'image' => $c->image,
                    'image_url' => $c->imageUrl(),
                    'banner_image' => $c->banner_image,
                    'banner_url' => $c->bannerUrl(),
                    'description' => $c->description,
                    'products_count' => (int) ($c->products_count ?? 0),
                    'children_count' => (int) ($c->children_count ?? 0),
                    'settings' => $c->settingBag(),
                    'children' => $build((int) $c->id),
                ];
            })->values()->all();
        };

        return $build(0);
    }

    /** Flat options with depth prefix for selects (unlimited depth) */
    public static function treeOptions(?int $excludeId = null)
    {
        $tree = static::nestedTree();
        $options = collect();
        $walk = function (array $nodes, int $depth = 0) use (&$walk, &$options, $excludeId): void {
            foreach ($nodes as $n) {
                if ($excludeId && (int) $n['id'] === $excludeId) {
                    continue;
                }
                $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                $cat = new static;
                $cat->id = $n['id'];
                $cat->name = $prefix.$n['name'];
                $cat->parent_id = $n['parent_id'];
                $options->push($cat);
                if (! empty($n['children'])) {
                    $walk($n['children'], $depth + 1);
                }
            }
        };
        $walk($tree);

        return $options;
    }
}
