<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Defensive settings read/write for sparse + production Setting APIs
 * (getValue / get / value / setValue / set).
 */
class SettingsStore
{
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! class_exists(Setting::class)) {
            return $default;
        }

        try {
            if (method_exists(Setting::class, 'getValue')) {
                $v = Setting::getValue($key, $default);

                return $v === null ? $default : $v;
            }
            if (method_exists(Setting::class, 'get')) {
                $v = Setting::get($key, $default);

                return $v === null ? $default : $v;
            }
            if (method_exists(Setting::class, 'value')) {
                $v = Setting::value($key, $default);

                return $v === null ? $default : $v;
            }
        } catch (\Throwable) {
            //
        }

        return $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if (! class_exists(Setting::class)) {
            return;
        }

        try {
            if (method_exists(Setting::class, 'setValue')) {
                Setting::setValue($key, $value);

                return;
            }
            if (method_exists(Setting::class, 'set')) {
                Setting::set($key, $value);
            }
        } catch (\Throwable) {
            //
        }
    }

    /** Normalize admin/DB truthy values ("0", "false", 0, false → false). */
    public static function toBool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }
        if (is_string($value)) {
            $s = strtolower(trim($value));
            if ($s === '' || in_array($s, ['0', 'false', 'no', 'off', 'null'], true)) {
                return false;
            }
            if (in_array($s, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return (bool) $value;
    }
}
