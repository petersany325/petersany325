<?php
declare(strict_types=1);

namespace HddLand\Bot\Handlers;

use HddLand\Bot\Context;
use HddLand\Bot\Services\AiService;
use HddLand\Bot\Services\ContentService;
use HddLand\Bot\Services\FaqService;
use HddLand\Bot\Services\ForumService;
use HddLand\Bot\Services\ShopService;
use HddLand\Bot\Services\LicenseFlowService;
use HddLand\Bot\Services\SupportFormService;
use HddLand\Bot\Services\TicketService;
use HddLand\Bot\Support\Presenter;

final class MessageRouter
{
    public static function handle(Context $ctx): void
    {
        if (function_exists('ensure_license_sample_files')) {
            ensure_license_sample_files();
        }

        $message = $ctx->message ?? [];
        $chatId = $ctx->chatId;
        $userId = $ctx->userId;
        $lang = $ctx->lang;
        $text = $ctx->text;
        $from = $ctx->from;

        // License / receipt / activation media first
        if (isset($message['photo']) || isset($message['video']) || isset($message['document'])) {
            if (class_exists(LicenseFlowService::class, true) && LicenseFlowService::handleMedia($message, $lang)) {
                return;
            }
            if (!function_exists('feature_on') || feature_on('prodesk')) {
                if (function_exists('handle_request_media_message') && handle_request_media_message($message, $lang)) {
                    return;
                }
            }
        }

        // Bottom reply-keyboard MUST win over in-progress forms (Buy SEDIV / Support / …)
        if ($text !== '' && function_exists('resolve_reply_button_action') && function_exists('dispatch_reply_button_action')) {
            $action = resolve_reply_button_action($text);
            if ($action) {
                if (function_exists('clear_user_state')) {
                    clear_user_state($userId);
                }
                if (dispatch_reply_button_action($action, $chatId, $userId, $lang)) {
                    return;
                }
            }
        }

        // Account registration + license flow text steps
        if ($text !== '' && LicenseFlowService::handleText($chatId, $userId, $text, $lang)) {
            return;
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
                // Product flow: /start → language picker → translate bot → menus
                // Only skip when admin explicitly enables "start_with_menu"
                $skipGate = function_exists('cfg') && (int)cfg('start_with_menu', 0) === 1;
                $gateEnabled = !function_exists('feature_on') || feature_on('language_gate');
                if (!$skipGate && $gateEnabled) {
                    try {
                        if (function_exists('clear_user_state')) {
                            clear_user_state($userId);
                        }
                        $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
                        $gateText = function_exists('language_gate_text')
                            ? language_gate_text()
                            : "🌍 <b>Please select your language</b>\nلطفاً زبان خود را انتخاب کنید";
                        $kb = null;
                        if (function_exists('lang_keyboard_world')) {
                            $kb = lang_keyboard_world(true, 0, $detected);
                        } elseif (function_exists('lang_keyboard')) {
                            $kb = lang_keyboard(true);
                        }
                        if (!is_array($kb) || empty($kb['inline_keyboard'])) {
                            $kb = array('inline_keyboard' => array(
                                array(
                                    array('text' => '🇬🇧 English', 'callback_data' => 'startlang:en'),
                                    array('text' => '🇮🇷 فارسی', 'callback_data' => 'startlang:fa'),
                                ),
                            ));
                        }
                        send_message($chatId, $gateText, $kb);
                    } catch (\Throwable $e) {
                        @file_put_contents(
                            dirname(__DIR__, 2) . '/error.log',
                            date('c') . ' start language gate: ' . $e->getMessage() . "\n",
                            FILE_APPEND
                        );
                        send_message(
                            $chatId,
                            "🌍 <b>Please select your language</b>\nلطفاً زبان خود را انتخاب کنید",
                            array('inline_keyboard' => array(
                                array(
                                    array('text' => '🇬🇧 English', 'callback_data' => 'startlang:en'),
                                    array('text' => '🇮🇷 فارسی', 'callback_data' => 'startlang:fa'),
                                ),
                            ))
                        );
                    }
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

            case 'account':
            case 'myaccount':
                LicenseFlowService::showAccount($chatId, 0, $userId, $lang);
                break;

            case 'approvereceipt':
                if (!is_admin($userId)) {
                    send_message($chatId, '🔒 Admins only.');
                    break;
                }
                if (!ctype_digit(trim($arg))) {
                    send_message($chatId, 'Usage: /approvereceipt ID');
                    break;
                }
                $res = LicenseFlowService::approveReceipt((int)$arg, $userId);
                send_message($chatId, $res['msg']);
                break;

            case 'rejectreceipt':
                if (!is_admin($userId)) {
                    send_message($chatId, '🔒 Admins only.');
                    break;
                }
                if (!ctype_digit(trim($arg))) {
                    send_message($chatId, 'Usage: /rejectreceipt ID');
                    break;
                }
                $res = LicenseFlowService::rejectReceipt((int)$arg, $userId);
                send_message($chatId, $res['msg']);
                break;

            case 'license':
                if (function_exists('feature_on') && !feature_on('license')) {
                    send_message($chatId, Presenter::featureDisabled($lang, 'این بخش فعلاً غیرفعال است.', 'This section is currently disabled.'), main_keyboard($lang));
                    break;
                }
                LicenseFlowService::showLicenseEntry($chatId, 0, $userId, $lang);
                break;

            case 'cart':
            case 'orders':
            case 'checkout':
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
