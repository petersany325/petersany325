<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::remember('settings.all', 60, function () {
            return static::query()->pluck('value', 'key')->all();
        });

        return $all[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    public static function many(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value !== null && ! is_string($value)) {
                $value = (string) $value;
            }
            static::put((string) $key, $value);
        }
    }
}
