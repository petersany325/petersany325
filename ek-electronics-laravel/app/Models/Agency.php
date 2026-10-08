<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Agency extends Model
{
    protected $fillable = [
        'principal_name', 'principal_url', 'territory', 'agent_name', 'title', 'slug',
        'excerpt', 'body', 'coverage', 'website', 'email', 'phone', 'logo_url',
        'is_featured', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Agency $agency): void {
            if (blank($agency->slug)) {
                $agency->slug = Str::slug($agency->principal_name.'-'.$agency->territory);
            }
        });
    }

    /** @return list<string> */
    public function coverageLines(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->coverage))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
