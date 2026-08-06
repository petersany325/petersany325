<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'body', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (blank($page->slug) && filled($page->title)) {
                $page->slug = Str::slug($page->title) ?: Str::random(8);
            }
        });
    }
}
