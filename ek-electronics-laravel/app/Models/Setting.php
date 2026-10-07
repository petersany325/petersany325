<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting.$key", 60, function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.$key");
    }

    /** @param  array<string, mixed>  $data */
    public static function many(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            static::setValue((string) $key, $value === null ? null : (string) $value);
        }
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key, $default ? '1' : '0');

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    /** @return array<string, string|null> */
    public static function group(array $keys, array $defaults = []): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = static::getValue($key, $defaults[$key] ?? null);
        }

        return $out;
    }
}
