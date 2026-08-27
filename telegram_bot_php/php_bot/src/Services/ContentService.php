<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

final class ContentService
{
    public static function websiteText(string $lang): string
    {
        $custom = function_exists('content_text') ? content_text('website', $lang) : null;
        if ($custom) {
            return $custom;
        }
        $c = bot_config();
        $email = function_exists('cfg') ? trim((string)cfg('support_email', '')) : '';
        return "🌐 <b>" . htmlspecialchars((string)(function_exists('cfg') ? cfg('bot_title', 'HDD-Land') : 'HDD-Land')) . "</b>\n\n"
            . "• SeDiv 2026 — WD, Seagate, Toshiba, Samsung, Fujitsu\n"
            . "• SeDiv HITACHI ARM\n"
            . "• SeDiv HGST\n\n"
            . "Website: {$c['site_url']}\n"
            . "Forum: {$c['forum_url']}"
            . ($email !== '' ? "\nEmail: {$email}" : '');
    }

    public static function trainingText(string $lang): string
    {
        $custom = function_exists('content_text') ? content_text('training', $lang) : null;
        if ($custom) {
            return $custom;
        }
        $url = function_exists('cfg') ? cfg('training_url', bot_config()['forum_url']) : bot_config()['forum_url'];
        return "🎓 <b>SeDiv Training Center</b>\n\n"
            . "• SeDiv WD Training\n"
            . "• SeDiv Seagate (F3) Training\n"
            . "• SeDiv Toshiba / Samsung / Hitachi\n\n"
            . "Link: " . $url;
    }
}
