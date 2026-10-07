<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'status', 'meta_title', 'meta_description', 'show_in_menu',
    ];

    protected function casts(): array
    {
        return ['show_in_menu' => 'boolean'];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }
}
