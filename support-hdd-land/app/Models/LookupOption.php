<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LookupOption extends Model
{
    protected $fillable = ['group_key', 'name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public const GROUPS = [
        'admission_type' => 'نوع پذیرش',
        'service_type' => 'نوع خدمات',
        'repair_type' => 'نوع تعمیر',
        'warranty_type' => 'گارانتی',
        'hdd_capacity' => 'ظرفیت هارد',
        'brand_model' => 'برند و مدل',
        'reported_fault' => 'عیب اظهار مشتری',
        'accessories' => 'لوازم همراه',
        'appearance' => 'وضعیت ظاهری',
    ];

    public static function options(string $groupKey)
    {
        return Cache::remember("lookup_names_{$groupKey}", 30, function () use ($groupKey) {
            return static::query()
                ->where('group_key', $groupKey)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all();
        });
    }

    protected static function booted(): void
    {
        static::saved(function (self $m) {
            Cache::forget("lookup_{$m->group_key}");
            Cache::forget("lookup_names_{$m->group_key}");
        });
        static::deleted(function (self $m) {
            Cache::forget("lookup_{$m->group_key}");
            Cache::forget("lookup_names_{$m->group_key}");
        });
    }
}