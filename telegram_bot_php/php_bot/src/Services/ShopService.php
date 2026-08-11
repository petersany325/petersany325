<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Repositories\ProductRepository;
use HddLand\Bot\Support\Presenter;

final class ShopService
{
    public static function showList(int $chatId, int $msgId = 0, string $lang = 'en'): void
    {
        $rows = ProductRepository::activeList();
        $kb = ['inline_keyboard' => []];
        foreach ($rows as $p) {
            $kb['inline_keyboard'][] = [[
                'text' => $p['name'] . ' — $' . (int)$p['price'],
                'callback_data' => 'product:' . $p['id'],
            ]];
        }
        $kb['inline_keyboard'][] = [
            ['text' => $lang === 'fa' ? '💎 درخواست خرید' : '💎 Sales Request', 'callback_data' => 'req:sales'],
            ['text' => $lang === 'fa' ? '🛠️ پشتیبانی' : '🛠️ Support', 'callback_data' => 'req:support'],
        ];
        $kb['inline_keyboard'][] = [['text' => $lang === 'fa' ? '⬅️ بازگشت' : '⬅️ Back', 'callback_data' => 'main']];
        $custom = function_exists('content_text') ? content_text('shop', $lang) : null;
        $text = $custom ? $custom : ($lang === 'fa'
            ? "🛒 <b>فروشگاه SeDiv — HDD-Land</b>\n\nیک محصول را انتخاب کنید:"
            : "🛒 <b>SeDiv Shop — HDD-Land</b>\n\nSelect a product:");
        Presenter::editOrSend($chatId, $msgId, $text, $kb);
    }

    public static function showProduct(int $chatId, int $msgId, int $pid): void
    {
        $p = ProductRepository::findActive($pid);
        if (!$p) {
            Presenter::editOrSend($chatId, $msgId, 'Product not found.', main_keyboard());
            return;
        }
        $site = bot_config()['site_url'];
        $buy = !empty($p['buy_url']) ? $p['buy_url'] : $site;
        $label = !empty($p['link_label']) ? $p['link_label'] : '🌐 Buy / Details';
        $text = "📦 <b>" . htmlspecialchars((string)$p['name']) . "</b>\n\n"
            . htmlspecialchars((string)$p['description']) . "\n\n"
            . "💰 Price: <b>$" . number_format((float)$p['price'], 0) . "</b> / year";

        $kbRows = [];
        $kbRows[] = [['text' => $label, 'url' => $buy]];
        if (!empty($p['demo_url'])) {
            $kbRows[] = [['text' => '▶️ Demo / Info', 'url' => $p['demo_url']]];
        }
        $kbRows[] = [
            ['text' => '💎 Request Purchase', 'callback_data' => 'req:sales'],
            ['text' => '🛠️ Ask Support', 'callback_data' => 'req:support'],
        ];
        $kbRows[] = [['text' => '⬅️ Back to Shop', 'callback_data' => 'shop']];
        $kb = ['inline_keyboard' => $kbRows];

        if (!empty($p['image_url'])) {
            tg_api('sendPhoto', [
                'chat_id' => $chatId,
                'photo' => $p['image_url'],
                'caption' => strip_tags($text),
                'parse_mode' => 'HTML',
                'reply_markup' => $kb,
            ]);
            if (!empty($p['video_url'])) {
                tg_api('sendVideo', ['chat_id' => $chatId, 'video' => $p['video_url'], 'caption' => $p['name'] . ' video']);
            }
            return;
        }
        if (!empty($p['video_url'])) {
            tg_api('sendVideo', [
                'chat_id' => $chatId,
                'video' => $p['video_url'],
                'caption' => strip_tags($text),
                'reply_markup' => $kb,
            ]);
            return;
        }
        Presenter::editOrSend($chatId, $msgId, $text, $kb);
    }
}
