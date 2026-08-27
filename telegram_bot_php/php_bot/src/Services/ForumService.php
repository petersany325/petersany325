<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Support\Presenter;

final class ForumService
{
    public static function show(int $chatId, int $msgId = 0, string $lang = 'en'): void
    {
        $forum = bot_config()['forum_url'];
        $custom = function_exists('content_text') ? content_text('forum', $lang) : null;
        $text = $custom ? $custom : ($lang === 'fa'
            ? "📋 <b>انجمن HDD-Land</b>\n\nجامعه حرفه‌ای ریکاوری دیتا.\n\nبر اساس برند در فروم جستجو کنید:\n• Western Digital\n• Seagate\n• Toshiba\n• Samsung\n• Hitachi\n• Fujitsu"
            : "📋 <b>HDD-Land Forum</b>\n\nProfessional Data Recovery community.\n\nBrowse by brand on the forum:\n• Western Digital\n• Seagate\n• Toshiba\n• Samsung\n• Hitachi\n• Fujitsu");
        $kb = [
            'inline_keyboard' => [
                [['text' => $lang === 'fa' ? '🌐 باز کردن فروم' : '🌐 Open Forum', 'url' => $forum]],
                [['text' => $lang === 'fa' ? '⬅️ بازگشت' : '⬅️ Back', 'callback_data' => 'main']],
            ],
        ];
        Presenter::editOrSend($chatId, $msgId, $text, $kb);
    }
}
