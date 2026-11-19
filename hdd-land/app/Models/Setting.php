<?php

namespace App\Models;

use App\Support\DesignPageCache;
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

    private static ?bool $settingsTable = null;

    private static function settingsTableExists(): bool
    {
        if (self::$settingsTable !== null) {
            return self::$settingsTable;
        }
        try {
            return self::$settingsTable = Schema::hasTable('settings');
        } catch (\Throwable) {
            return self::$settingsTable = false;
        }
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (! self::settingsTableExists()) {
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

    /** Alias used by some plugins / production helpers. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    public static function setValue(string $key, mixed $value): void
    {
        try {
            if (! self::settingsTableExists()) {
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
            if (class_exists(DesignPageCache::class)) {
                DesignPageCache::forget();
            }
        } catch (\Throwable) {
            //
        }
    }

    /** Alias used by some plugins / production helpers. */
    public static function set(string $key, mixed $value): void
    {
        static::setValue($key, $value);
    }
}
