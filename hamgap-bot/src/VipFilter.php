<?php
declare(strict_types=1);

/** VIP club bad-word filter driven by admin setting `vip_bad_words`. */
final class VipFilter
{
    /** @return list<string> */
    public static function words(Settings $settings): array
    {
        $raw = $settings->get('vip_bad_words', Settings::DEFAULTS['vip_bad_words'] ?? '');
        $parts = preg_split('/[,،\n]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = mb_strtolower($p);
            }
        }
        return array_values(array_unique($out));
    }

    public static function containsBadWord(?string $text, Settings $settings): bool
    {
        $text = mb_strtolower(trim((string)$text));
        if ($text === '') {
            return false;
        }
        foreach (self::words($settings) as $w) {
            if ($w !== '' && mb_strpos($text, $w) !== false) {
                return true;
            }
        }
        return false;
    }
}
