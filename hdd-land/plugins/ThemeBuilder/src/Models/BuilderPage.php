<?php

namespace Plugins\ThemeBuilder\src\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderPage extends Model
{
    protected $table = 'builder_pages';

    protected $fillable = [
        'title',
        'slug',
        'path',
        'status',
        'seo_title',
        'seo_description',
        'menu_label',
        'menu_group',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function isPublished(): bool
    {
        return ($this->status ?? '') === 'publish';
    }

    /** @return list<array<string, mixed>> */
    public function widgets(): array
    {
        return is_array($this->blocks) ? array_values($this->blocks) : [];
    }
}
