<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight settings store used by homepage / ThemeBuilder when the
 * full application Setting model is not present in this sparse tree.
 * Production may already ship a richer Setting class; this mirrors the
 * getValue / setValue API those helpers expect.
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }
            $all = Cache::remember('settings.all', 60, function () {
                return static::query()->pluck('value', 'key')->all();
            });
            if (! array_key_exists($key, $all)) {
                return $default;
            }
            $value = $all[$key];
            if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            return $value;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function setValue(string $key, mixed $value): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif ($value !== null && ! is_string($value)) {
                $value = (string) $value;
            }
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget('settings.all');
        } catch (\Throwable) {
            //
        }
    }
}
