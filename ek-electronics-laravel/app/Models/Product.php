<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'description', 'grade',
        'price', 'cost', 'stock', 'image_path', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return asset('assets/img/hdd.jpg');
        }
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return asset($this->image_path);
    }
}
