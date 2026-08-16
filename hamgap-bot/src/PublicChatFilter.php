<?php
declare(strict_types=1);

/**
 * Blocks relationship / sex / meetup / money / vulgar / partner talk in public chat.
 * Persian + Finglish + common spellings. Matching is aggressive (substring after normalize).
 */
final class PublicChatFilter
{
    /** @return list<string> */
    public static function blockedTokens(): array
    {
        return [
            // sex / vulgar — FA
            'سکس', 'سکسی', 'سكسی', 'سكش', 'جق', 'کص', 'كس', 'کصکش', 'کسکش',
            'کیر', 'كير', 'کونی', 'كونی', 'جنده', 'مادرجنده', 'لاشی', 'لاشیه',
            'گایید', 'بگا', 'بگام', 'میگامت', 'دیوث', 'چاقال', 'کون', 'كون',
            'ممه', 'لخت', 'برهنه', 'پورن', 'پورنو', 'شهوتی', 'ارگاسم',
            // relationship / partner — FA
            'رابطه', 'پارتنر', 'پارنتر', 'پارتنری', 'پارتنرشیپ',
            'دوستدختر', 'دوستپسر', 'دوست دختر', 'دوست پسر', 'صیغه', 'صيغه',
            // meetup / plan — FA
            'برنامه', 'قرار بذاریم', 'قراربذاریم', 'میای خونه', 'بیای پیشم',
            'بیا پیشم', 'هتل', 'اتاق خلوت',
            // money — FA
            'پول', 'پول بده', 'پولبده', 'کارت به کارت', 'کارتبکارت',
            'شبا', 'واریز', 'هزینه',
            // sex / vulgar — finglish/en
            'sex', 'seks', 'sexy', 'sexx', 'seksi', 'jens', 'jensi', 'jensei',
            'kos', 'koss', 'kosi', 'kir', 'koni', 'koon', 'jende', 'jnde',
            'koskesh', 'madarjende', 'fuck', 'fck', 'fuk', 'f*ck', 'porn',
            'porno', 'xxx', 'pussy', 'dick', 'cock', 'nude', 'naked',
            'blowjob', 'anal', 'horny', 'orgasm',
            // partner / relationship — finglish
            'partner', 'parnter', 'partener', 'parter', 'partenr', 'partnr',
            'relationship', 'rabete', 'rabeteh', 'rabete', 'sighe', 'sigheh',
            'girlfriend', 'boyfriend', 'gf', 'bf', 'dustdokhtar', 'dustpesar',
            // meetup — finglish
            'barname', 'barnameh', 'barnam', 'gharar', 'meetup', 'meetme',
            'comeover', 'hotel', 'otagh', 'otag',
            // money — finglish
            'money', 'pool', 'pol', 'poolbede', 'polbede', 'variz', 'shaba',
            'cardbecard', 'paypal', 'cash',
        ];
    }

    /** @return list<string> */
    private static function phrasePatterns(): array
    {
        return [
            '/\b(sex|seks|sexy|sexx|porn|xxx|fuck|fck)\b/i',
            '/\b(partner|parnter|partener|parter|partnr)\b/i',
            '/\b(girlfriend|boyfriend|meetup|meet\s*up)\b/i',
            '/\b(money|paypal|cash)\b/i',
            '/\b(pool|pol)\b/i',
            '/(سکس|سكسی|رابطه|پارتنر|پارنتر|برنامه|پول|جنده|کص|کیر)/u',
            '/(میای|بیای|بیا)\s*(خونه|پیشم|پیش\s*من)/u',
            '/(برنامه|قرار)\s*(بذاریم|داری|داریم|چیه)/u',
            '/پول\s*(بده|میخوام|لازم|داری|بده\s*بهم)/u',
            '/کارت\s*(به|ب)\s*کارت/u',
            '/دوست\s*(دختر|پسر)/u',
        ];
    }

    public static function normalize(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }
        // Arabic Yeh/Kaf/Heh → Persian
        $text = str_replace(
            ['ي', 'ى', 'ك', 'ة', 'ؤ', 'إ', 'أ', 'آ'],
            ['ی', 'ی', 'ک', 'ه', 'و', 'ا', 'ا', 'ا'],
            $text
        );
        // Zero-width, tatweel, soft hyphen
        $text = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{FEFF}\x{0640}\x{00AD}]+/u', '', $text) ?? $text;
        // Keep letters/numbers/spaces only
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    public static function compact(string $normalized): string
    {
        return preg_replace('/\s+/u', '', $normalized) ?? $normalized;
    }

    public static function isBlocked(?string $text): bool
    {
        $raw = trim((string)$text);
        if ($raw === '') {
            return false;
        }
        $norm = self::normalize($raw);
        if ($norm === '') {
            return false;
        }
        $compact = self::compact($norm);
        // Also a latin-only compact for finglish inside mixed strings
        $latin = strtolower(preg_replace('/[^a-z0-9]+/i', '', $raw) ?? '');

        foreach (self::blockedTokens() as $token) {
            $t = self::normalize($token);
            if ($t === '') {
                continue;
            }
            $tc = self::compact($t);
            $isLatin = (bool)preg_match('/^[a-z0-9]+$/i', $token);
            $shortLatin = $isLatin && strlen($token) <= 4;

            if ($shortLatin) {
                // Word-boundary only — avoid "anal" in "analysis", "pol" in "police"
                if (preg_match('/\b' . preg_quote(strtolower($token), '/') . '\b/i', $norm)
                    || preg_match('/\b' . preg_quote(strtolower($token), '/') . '\b/i', $raw)
                ) {
                    return true;
                }
                continue;
            }

            if (self::contains($norm, $t) || ($tc !== '' && self::contains($compact, $tc))) {
                return true;
            }
            if ($isLatin && $latin !== '' && str_contains($latin, strtolower($token))) {
                return true;
            }
        }

        foreach (self::phrasePatterns() as $re) {
            if (@preg_match($re, $norm) || @preg_match($re, $raw)) {
                return true;
            }
        }
        return false;
    }

    private static function contains(string $haystack, string $needle): bool
    {
        if ($needle === '' || $haystack === '') {
            return false;
        }
        if (function_exists('mb_strpos')) {
            return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
        }
        return str_contains($haystack, $needle);
    }

    /** True when mode should use the public-chat filter (everything except hot/vip). */
    public static function shouldFilterMode(?string $mode): bool
    {
        $mode = strtolower(trim((string)$mode));
        if ($mode === '' || $mode === 'null' || $mode === 'normal') {
            return true;
        }
        return $mode !== 'hot' && $mode !== 'vipclub';
    }
}
