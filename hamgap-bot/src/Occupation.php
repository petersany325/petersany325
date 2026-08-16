<?php
declare(strict_types=1);

/**
 * Optional self-declared occupation / education tags for profile + search.
 * Not verified credentials — UI should treat as خوداظهاری.
 */
final class Occupation
{
    public const STUDENT = 'student';
    public const GRADUATE = 'graduate';
    public const ENGINEER = 'engineer';
    public const DOCTOR = 'doctor';
    public const LAWYER = 'lawyer';
    public const BUSINESS = 'business';
    public const ARTS = 'arts';
    public const OTHER = 'other';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::STUDENT,
            self::GRADUATE,
            self::ENGINEER,
            self::DOCTOR,
            self::LAWYER,
            self::BUSINESS,
            self::ARTS,
            self::OTHER,
        ];
    }

    public static function isValid(?string $v): bool
    {
        return in_array((string)$v, self::all(), true);
    }

    public static function label(?string $v): string
    {
        return match ((string)$v) {
            self::STUDENT => 'دانشجو',
            self::GRADUATE => 'فارغ‌التحصیل',
            self::ENGINEER => 'مهندس',
            self::DOCTOR => 'پزشک / کادر درمان',
            self::LAWYER => 'حقوق / وکالت',
            self::BUSINESS => 'کسب‌وکار / مدیریت',
            self::ARTS => 'هنر / رسانه',
            self::OTHER => 'سایر',
            default => '—',
        };
    }

    public static function short(?string $v): string
    {
        return match ((string)$v) {
            self::STUDENT => 'دانشجو',
            self::GRADUATE => 'فارغ‌التحصیل',
            self::ENGINEER => 'مهندس',
            self::DOCTOR => 'پزشک',
            self::LAWYER => 'حقوق',
            self::BUSINESS => 'کسب‌وکار',
            self::ARTS => 'هنر',
            self::OTHER => 'سایر',
            default => '',
        };
    }

    public static function emoji(?string $v): string
    {
        return match ((string)$v) {
            self::STUDENT => '📚',
            self::GRADUATE => '🎓',
            self::ENGINEER => '🛠',
            self::DOCTOR => '🩺',
            self::LAWYER => '⚖️',
            self::BUSINESS => '💼',
            self::ARTS => '🎨',
            self::OTHER => '✨',
            default => '',
        };
    }

    public static function badge(?string $v): string
    {
        $v = (string)$v;
        if (!self::isValid($v)) {
            return '';
        }
        return self::emoji($v) . ' ' . self::short($v);
    }
}
