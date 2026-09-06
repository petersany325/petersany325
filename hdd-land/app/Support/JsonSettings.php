<?php

namespace App\Support;

use App\Models\Setting;

class JsonSettings
{
    /** @param  array<string,mixed>  $defaults */
    public static function get(string $key, array $defaults): array
    {
        $raw = Setting::getValue($key, null);
        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true) ?: [];
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        return array_merge($defaults, is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string,mixed>  $defaults
     * @param  array<string,mixed>  $data
     * @param  list<string>  $boolKeys
     * @param  array<string,callable>  $map
     * @return array<string,mixed>
     */
    public static function save(string $key, array $defaults, array $data, array $boolKeys = [], array $map = []): array
    {
        $cur = static::get($key, $defaults);
        $patch = [];
        foreach ($boolKeys as $bk) {
            $patch[$bk] = ! empty($data[$bk]);
        }
        foreach ($map as $k => $fn) {
            if (array_key_exists($k, $data)) {
                $patch[$k] = $fn($data[$k]);
            }
        }
        $merged = array_merge($cur, $patch);
        Setting::setValue($key, $merged);

        return $merged;
    }
}
