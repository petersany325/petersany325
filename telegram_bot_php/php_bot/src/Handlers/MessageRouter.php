<?php
declare(strict_types=1);

namespace HddLand\Bot\Handlers;

use HddLand\Bot\Context;
use HddLand\Bot\Services\AiService;
use HddLand\Bot\Services\ContentService;
use HddLand\Bot\Services\FaqService;
use HddLand\Bot\Services\ForumService;
use HddLand\Bot\Services\ShopService;
use HddLand\Bot\Services\SupportFormService;
use HddLand\Bot\Services\TicketService;
use HddLand\Bot\Support\Presenter;

final class MessageRouter
{
    public static function handle(Context $ctx): void
    {
        $message = $ctx->message ?? [];
        $chatId = $ctx->chatId;
        $userId = $ctx->userId;
        $lang = $ctx->lang;
        $text = $ctx->text;
        $from = $ctx->from;

        // Media for Pro Desk
        if (isset($message['photo']) || isset($message['video']) || isset($message['document'])) {
            if (!function_exists('feature_on') || feature_on('prodesk')) {
                if (function_exists('handle_request_media_message') && handle_request_media_message($message, $lang)) {
                    return;
                }
            }
        }

        // Advanced support / ticket form + optional phone gate for My Tickets
        if ($text !== '') {
            if (SupportFormService::handleText($chatId, $userId, $text, $lang)) {
                return;
            }
            if (SupportFormService::handleMyTicketsPhone($chatId, $userId, $text, $lang)) {
                return;
            }
        }

        if ($text === '' || ($text[0] ?? '') !== '/') {
            if ($text !== '') {
                // Bottom reply-keyboard buttons arrive as plain text (not callbacks)
                if (function_exists('resolve_reply_button_action') && function_exists('dispatch_reply_button_action')) {
                    $action = resolve_reply_button_action($text);
                    if ($action && dispatch_reply_button_action($action, $chatId, $userId, $lang)) {
                        return;
                    }
                }
                if (function_exists('feature_on') && feature_on('prodesk') && function_exists('handle_request_text') && handle_request_text($chatId, $userId, $text, $lang)) {
                    return;
                }
                if (!function_exists('feature_on') || feature_on('auto_faq_search')) {
                    $hits = \HddLand\Bot\Repositories\FaqRepository::search($text, $lang);
                    if ($hits) {
                        $lines = ["❓ <b>Related FAQ:</b>\n"];
                        foreach ($hits as $f) {
                            $lines[] = '<b>' . htmlspecialchars((string)$f['question']) . "</b>\n" . htmlspecialchars((string)$f['answer']) . "\n";
                        }
                        send_message($chatId, implode("\n", $lines), faq_keyboard(null, $lang));
                        return;
                    }
                }
                send_message(
                    $chatId,
                    $lang === 'fa' ? 'از دکمه‌های پایین یا /menu استفاده کنید.' : 'Use the buttons below or /menu.',
                    function_exists('main_reply_keyboard') ? main_reply_keyboard($lang) : main_keyboard($lang)
                );
            }
            return;
        }

        if (in_array($text, ['/cancel', '/done'], true) || strpos($text, '/cancel') === 0 || strpos($text, '/done') === 0) {
            if (function_exists('handle_request_text') && handle_request_text($chatId, $userId, explode(' ', $text)[0], $lang)) {
                return;
            }
        }

        $parts = preg_split('/\s+/', $text, 2) ?: [];
        $cmd = strtolower(explode('@', ltrim((string)($parts[0] ?? ''), '/'))[0]);
        $arg = (string)($parts[1] ?? '');

        switch ($cmd) {
            case 'start':
                if (function_exists('feature_on') && feature_on('language_gate') && empty(cfg('start_with_menu', 0))) {
                    $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
                    send_message($chatId, language_gate_text(), function_exists('lang_keyboard_world') ? lang_keyboard_world(true, 0, $detected) : lang_keyboard(true));
                } else {
                    if (function_exists('main_reply_keyboard')) {
                        send_message($chatId, welcome_text($lang), main_reply_keyboard($lang));
                        send_message($chatId, $lang === 'fa' ? '📑 منوی سریع:' : '📑 Quick menu:', main_keyboard($lang));
                    } else {
                        send_message($chatId, welcome_text($lang), main_keyboard($lang));
                    }
                }
                break;

            case 'menu':
                if (function_exists('main_reply_keyboard')) {
                    send_message($chatId, $lang === 'fa' ? '📑 <b>منوی اصلی</b>' : '📑 <b>Main Menu</b>', main_reply_keyboard($lang));
                    send_message($chatId, $lang === 'fa' ? 'دکمه‌های شیشه‌ای:' : 'Menu buttons:', main_keyboard($lang));
                } else {
                    send_message($chatId, $lang === 'fa' ? '📑 <b>منوی پیشرفته</b>' : '📑 <b>Main Menu</b>', main_keyboard($lang));
                }
                break;

            case 'lang':
            case 'language':
                $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
                send_message($chatId, language_gate_text(), function_exists('lang_keyboard_world') ? lang_keyboard_world(false, 0, $detected) : lang_keyboard(false));
                break;

            case 'faq':
                if (function_exists('feature_on') && !feature_on('faq')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'FAQ فعلاً غیرفعال است.', 'FAQ is disabled.'), main_keyboard($lang));
                    break;
                }
                if ($arg !== '') {
                    $hits = \HddLand\Bot\Repositories\FaqRepository::search($arg, $lang);
                    if (!$hits) {
                        send_message($chatId, 'No FAQ found for: ' . htmlspecialchars($arg), faq_keyboard(null, $lang));
                    } else {
                        $lines = ["❓ <b>FAQ:</b>\n"];
                        foreach ($hits as $f) {
                            $lines[] = '<b>' . htmlspecialchars((string)$f['question']) . "</b>\n" . htmlspecialchars((string)$f['answer']) . "\n";
                        }
                        send_message($chatId, implode("\n", $lines), faq_keyboard(null, $lang));
                    }
                } else {
                    FaqService::showHub($chatId, $lang);
                }
                break;

            case 'help':
                send_message($chatId, help_text($lang), main_keyboard($lang));
                break;

            case 'support':
            case 'sales':
            case 'pro':
            case 'desk':
                if (function_exists('feature_on') && !feature_on('prodesk')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'میز حرفه‌ای غیرفعال است.', 'Pro Desk is disabled.'), main_keyboard($lang));
                    break;
                }
                show_request_hub($chatId, 0, $lang);
                break;

            case 'website':
                send_message($chatId, ContentService::websiteText($lang), main_keyboard($lang));
                break;

            case 'training':
                send_message($chatId, ContentService::trainingText($lang), main_keyboard($lang));
                break;

            case 'shop':
                if (function_exists('feature_on') && !feature_on('shop')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'فروشگاه غیرفعال است.', 'Shop is disabled.'), main_keyboard($lang));
                    break;
                }
                ShopService::showList($chatId, 0, $lang);
                break;

            case 'forum':
                if (function_exists('feature_on') && !feature_on('forum')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'فروم غیرفعال است.', 'Forum is disabled.'), main_keyboard($lang));
                    break;
                }
                ForumService::show($chatId, 0, $lang);
                break;

            case 'ticket':
            case 'supportform':
                if (function_exists('feature_on') && !feature_on('tickets') && !feature_on('prodesk')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'تیکت غیرفعال است.', 'Tickets are disabled.'), main_keyboard($lang));
                    break;
                }
                SupportFormService::start($chatId, $userId, $lang, 'ticket');
                break;

            case 'mytickets':
                SupportFormService::showMyTickets($chatId, $userId, $lang);
                break;

            case 'tickets':
                if (!is_admin($userId)) {
                    send_message($chatId, '🔒 Admins only.');
                    break;
                }
                TicketService::showOpen($chatId);
                break;

            case 'replyticket':
                if (!is_admin($userId)) {
                    send_message($chatId, '🔒 Admins only.');
                    break;
                }
                $bits = preg_split('/\s+/', $arg, 2) ?: [];
                if (count($bits) < 2 || !ctype_digit((string)$bits[0])) {
                    send_message($chatId, 'Usage: /replyticket ID message');
                    break;
                }
                TicketService::reply($chatId, $userId, (int)$bits[0], (string)$bits[1]);
                break;

            case 'closeticket':
                if (!is_admin($userId)) {
                    send_message($chatId, '🔒 Admins only.');
                    break;
                }
                if (!ctype_digit(trim($arg))) {
                    send_message($chatId, 'Usage: /closeticket ID');
                    break;
                }
                TicketService::close($chatId, (int)$arg);
                break;

            case 'ask':
                if (function_exists('feature_on') && !feature_on('ai')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'دستیار هوشمند غیرفعال است.', 'AI is disabled.'), main_keyboard($lang));
                    break;
                }
                AiService::ask($chatId, $arg);
                break;

            case 'cart':
            case 'orders':
            case 'checkout':
            case 'license':
            case 'renew':
            case 'demo':
            case 'profile':
            case 'feedback':
            case 'referral':
            case 'contact':
            case 'brands':
            case 'news':
            case 'miniapp':
            case 'vipdl':
            case 'vip':
                \HddLand\Bot\Services\ExtraMenusService::show($cmd === 'vip' ? 'vipdl' : $cmd, $chatId, 0, $userId, $lang);
                break;

            default:
                send_message($chatId, 'Unknown command. Try /help', main_keyboard($lang));
        }
    }
}
