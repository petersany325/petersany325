<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Repositories\FaqRepository;
use HddLand\Bot\Support\Presenter;

final class FaqService
{
    public static function showHub(int $chatId, string $lang): void
    {
        send_message(
            $chatId,
            $lang === 'fa' ? '❓ <b>سوالات متداول</b>' : '❓ <b>Frequently Asked Questions</b>',
            faq_keyboard(null, $lang)
        );
    }

    public static function searchAndReply(int $chatId, string $query, string $lang, string $title = 'FAQ'): void
    {
        $hits = FaqRepository::search($query, $lang);
        if (!$hits) {
            send_message($chatId, 'No FAQ found for: ' . htmlspecialchars($query), faq_keyboard(null, $lang));
            return;
        }
        $lines = ["❓ <b>{$title}:</b>\n"];
        foreach ($hits as $f) {
            $lines[] = '<b>' . htmlspecialchars((string)$f['question']) . "</b>\n" . htmlspecialchars((string)$f['answer']) . "\n";
        }
        send_message($chatId, implode("\n", $lines), faq_keyboard(null, $lang));
    }

    public static function showCategory(int $chatId, int $msgId, string $cat, string $lang): void
    {
        $title = ($cat === 'all') ? ($lang === 'fa' ? 'همه سوالات' : 'All FAQs') : $cat;
        Presenter::editOrSend($chatId, $msgId, "❓ <b>{$title}</b>", faq_keyboard($cat === 'all' ? null : $cat, $lang));
    }

    public static function showOne(int $chatId, int $msgId, int $faqId, string $lang): void
    {
        $f = FaqRepository::findActive($faqId);
        if (!$f) {
            Presenter::editOrSend($chatId, $msgId, 'FAQ not found.', faq_keyboard(null, $lang));
            return;
        }
        if (function_exists('localize_faq_row')) {
            $f = localize_faq_row($f, $lang);
        }
        $text = '❓ <b>' . htmlspecialchars((string)$f['question']) . "</b>\n\n" . htmlspecialchars((string)$f['answer']);
        $back = $lang === 'fa' ? '⬅️ بازگشت به FAQ' : '⬅️ Back to FAQ';
        $main = $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu';
        $kb = [
            'inline_keyboard' => [
                [['text' => $back, 'callback_data' => 'faqcat:all']],
                [['text' => $main, 'callback_data' => 'main']],
            ],
        ];
        Presenter::editOrSend($chatId, $msgId, $text, $kb);
    }
}
