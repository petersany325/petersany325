<?php
declare(strict_types=1);

final class SettingsStore
{
    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $row = Database::query('SELECT `value` FROM settings WHERE `key`=?', [$key])->fetch();
            if (!$row) {
                return $default;
            }
            return $row['value'] !== null ? (string) $row['value'] : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, ?string $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)',
            [$key, $value]
        );
    }

    /** @param array<string,string|null> $map */
    public static function setMany(array $map): void
    {
        foreach ($map as $k => $v) {
            self::set((string) $k, $v === null ? null : (string) $v);
        }
    }

    /** @return array<string,string> */
    public static function getMany(array $keys, array $defaults = []): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = (string) (self::get($k, $defaults[$k] ?? '') ?? ($defaults[$k] ?? ''));
        }
        return $out;
    }

    public static function getJson(string $key, array $default = []): array
    {
        $raw = self::get($key);
        if ($raw === null || $raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $key, array $value): void
    {
        self::set($key, json_encode($value, JSON_UNESCAPED_UNICODE));
    }
}
