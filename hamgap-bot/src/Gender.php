<?php
declare(strict_types=1);

/** Gender values used across registration, search, and matching. */
final class Gender
{
    public const MALE = 'male';
    public const FEMALE = 'female';
    /** Shemale / non-binary option shown as «شیمیل / دوجنسه». */
    public const SHEMALE = 'shemale';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::MALE, self::FEMALE, self::SHEMALE];
    }

    public static function isValid(?string $g): bool
    {
        return in_array((string)$g, self::all(), true);
    }

    public static function isFilter(?string $g): bool
    {
        return $g === 'any' || self::isValid($g);
    }

    public static function label(?string $g): string
    {
        return match ((string)$g) {
            self::FEMALE => 'دختر',
            self::MALE => 'پسر',
            self::SHEMALE => 'شیمیل / دوجنسه',
            default => '—',
        };
    }

    public static function short(?string $g): string
    {
        return match ((string)$g) {
            self::FEMALE => 'دختر',
            self::MALE => 'پسر',
            self::SHEMALE => 'شیمیل',
            default => '—',
        };
    }

    public static function emoji(?string $g): string
    {
        return match ((string)$g) {
            self::FEMALE => '👩',
            self::MALE => '👨',
            self::SHEMALE => '🌈',
            default => '•',
        };
    }
}
