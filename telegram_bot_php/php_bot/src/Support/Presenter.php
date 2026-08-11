<?php
declare(strict_types=1);

namespace HddLand\Bot\Support;

/**
 * Thin Telegram UI helpers (presentation only).
 */
final class Presenter
{
    public static function editOrSend(int $chatId, int $msgId, string $text, ?array $kb = null): void
    {
        if ($msgId > 0) {
            edit_message($chatId, $msgId, $text, $kb);
        } else {
            send_message($chatId, $text, $kb);
        }
    }

    public static function featureDisabled(string $lang, string $fa, string $en): string
    {
        return $lang === 'fa' ? $fa : $en;
    }
}
