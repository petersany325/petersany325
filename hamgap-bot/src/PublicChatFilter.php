<?php
declare(strict_types=1);

/**
 * Blocks relationship / sex / meetup / money / vulgar / partner talk in public (normal) chat.
 * Matches Persian and Finglish (Latin) variants.
 */
final class PublicChatFilter
{
    /**
     * Compact tokens searched inside a normalized haystack (spaces stripped for Latin).
     * Keep terms specific enough to avoid blocking normal friendly chat.
     *
     * @return list<string>
     */
    public static function blockedTokens(): array
    {
        return [
            // Persian — sex / vulgar
            'سکس', 'سکسی', 'جق', 'کص', 'کسکش', 'کیر', 'کونی', 'جنده', 'مادرجنده',
            'لاشی', 'لاشیه', 'گایید', 'بگا', 'بگام', 'میگامت', 'دیوث', 'چاقال',
            'کون', 'ممه', 'سینههات', 'لخت', 'برهنه', 'پورن', 'سکسیی',
            // Persian — relationship / partner
            'رابطه', 'پارتنر', 'پارنتر', 'پارتنری', 'دوست‌دختر', 'دوستدختر',
            'دوست‌پسر', 'دوستپسر', 'صیغه', 'صيغه',
            // Persian — meetup / plan
            'برنامه', 'برنامه‌ای', 'برنامهء', 'قراربذاریم', 'قراربذاریم',
            'میایخونه', 'بیایپیشم', 'بیاپیشم', 'هتل', 'مهمونی‌خصوصی',
            // Persian — money / scam-ish asks in chat
            'پول‌بده', 'پولبده', 'برام‌پول', 'کارت‌به‌کارت', 'کارتبکارت',
            'شبا', 'واریزکن', 'برات‌واریز', 'هزینه‌اتو',
            // Finglish / English — sex / vulgar
            'sex', 'seks', 'sexy', 'jensei', 'jensi', 'kos', 'koss', 'kir', 'koni',
            'jende', 'jnde', 'gayeedi', 'begam', 'fuck', 'fck', 'fuk', 'porn',
            'xxx', 'pussy', 'dick', 'cock', 'nude', 'naked', 'blowjob', 'anal',
            // Finglish — partner / relationship
            'partner', 'parnter', 'partener', 'parter', 'partenr',
            'relationship', 'rabete', 'rabeteh', 'dustdokhtar', 'dustpesar',
            'girlfriend', 'boyfriend', 'sighe', 'sigheh',
            // Finglish — meetup / plan
            'barname', 'barnameh', 'gharar', 'meetup', 'meetme', 'comeover',
            'hotel', 'otagh', 'otag',
            // Finglish — money
            'poolbede', 'polbede', 'bede pool', 'money', 'cardbecard', 'shaba',
            'variz', 'paypal',
        ];
    }

    /** Extra multi-word / phrase patterns on spaced normalized text. */
    private static function phrasePatterns(): array
    {
        return [
            '/\b(sex|seks|sexy)\b/u',
            '/\b(partner|parnter|partener|parter)\b/u',
            '/\b(fuck|fck|porn|xxx)\b/u',
            '/\b(meetup|meet\s*up|come\s*over)\b/u',
            '/\b(girl\s*friend|boy\s*friend)\b/u',
            '/\b(need|want|give)\s+(money|pool|pol)\b/u',
            '/\b(pool|pol)\s+(bede|bedeh|bezan)\b/u',
            '/رابطه\s*(جنسی|سکسی)?/u',
            '/درخواست\s*(رابطه|سکس|پارتنر|پول)/u',
            '/(میای|بیای|بیا)\s*(خونه|پیشم|پیش\s*من)/u',
            '/(برنامه|قرار)\s*(بذاریم|داری|داریم|چیه)/u',
            '/پول\s*(بده|میخوام|لازم|داری)/u',
            '/کارت\s*(به|ب)\s*کارت/u',
        ];
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        if ($text === '') {
            return '';
        }
        // Arabic Yeh/Kaf → Persian
        $text = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $text);
        // Zero-width / tatweel / punctuation noise
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{0640}]+/u', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /** Compact form for Latin token matching (spaces removed). */
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

        foreach (self::blockedTokens() as $token) {
            $t = self::normalize($token);
            if ($t === '') {
                continue;
            }
            // Persian / multi-byte: substring on spaced + compact
            if (mb_strpos($norm, $t) !== false || mb_strpos($compact, self::compact($t)) !== false) {
                return true;
            }
        }

        foreach (self::phrasePatterns() as $re) {
            if (preg_match($re, $norm)) {
                return true;
            }
        }
        return false;
    }
}
