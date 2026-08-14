<?php
declare(strict_types=1);

namespace HddLand\Bot\Handlers;

use HddLand\Bot\Context;
use HddLand\Bot\Repositories\FaqRepository;
use HddLand\Bot\Repositories\UserRepository;
use HddLand\Bot\Services\ContentService;
use HddLand\Bot\Services\FaqService;
use HddLand\Bot\Services\ForumService;
use HddLand\Bot\Services\LicenseFlowService;
use HddLand\Bot\Services\ShopService;
use HddLand\Bot\Services\SupportFormService;
use HddLand\Bot\Support\Presenter;

final class CallbackRouter
{
    public static function handle(Context $ctx): void
    {
        $id = $ctx->callbackId;
        $data = $ctx->callbackData;
        $chatId = $ctx->chatId;
        $msgId = $ctx->messageId;
        $userId = $ctx->userId;
        $lang = $ctx->lang;
        $from = $ctx->from;

        if (LicenseFlowService::handleCallback($data, $id, $chatId, $msgId, $userId, $lang)) {
            return;
        }

        if ($data === 'shop') {
            answer_callback($id);
            if (function_exists('feature_on') && !feature_on('shop')) {
                Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'فروشگاه غیرفعال است.', 'Shop is disabled.'), main_keyboard($lang));
            } else {
                ShopService::showList($chatId, $msgId, $lang);
            }
            return;
        }

        if ($data === 'forum') {
            answer_callback($id);
            if (function_exists('feature_on') && !feature_on('forum')) {
                Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'فروم غیرفعال است.', 'Forum is disabled.'), main_keyboard($lang));
            } else {
                ForumService::show($chatId, $msgId, $lang);
            }
            return;
        }

        if ($data === 'support' || $data === 'reqhub') {
            answer_callback($id);
            if (function_exists('feature_on') && !feature_on('prodesk')) {
                Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'میز حرفه‌ای غیرفعال است.', 'Pro Desk is disabled.'), main_keyboard($lang));
            } else {
                show_request_hub($chatId, $msgId, $lang);
            }
            return;
        }

        if ($data === 'req:support') {
            answer_callback($id);
            if (function_exists('clear_user_state')) {
                clear_user_state($userId);
            }
            SupportFormService::start($chatId, $userId, $lang, 'support');
            return;
        }

        if ($data === 'support_cancel') {
            answer_callback($id);
            clear_user_state($userId);
            Presenter::editOrSend(
                $chatId,
                $msgId,
                $lang === 'fa' ? '❌ لغو شد.' : '❌ Cancelled.',
                main_keyboard($lang)
            );
            return;
        }

        if ($data === 'mytickets') {
            answer_callback($id);
            SupportFormService::showMyTickets($chatId, $userId, $lang, $msgId);
            return;
        }

        if (strpos($data, 'ticket_reply:') === 0) {
            $tid = (int)substr($data, 13);
            answer_callback($id);
            SupportFormService::beginUserReply($chatId, $userId, $tid, $lang, $msgId);
            return;
        }

        if (strpos($data, 'ticket:') === 0) {
            $tid = (int)substr($data, 7);
            answer_callback($id);
            SupportFormService::showTicketThread($chatId, $userId, $tid, $lang, $msgId);
            return;
        }
        if ($data === 'req:sales') {
            answer_callback($id);
            start_request_flow($chatId, $userId, 'sales', $lang);
            return;
        }
        if ($data === 'req:mediahelp') {
            answer_callback($id);
            $t = $lang === 'fa'
                ? "📎 <b>ارسال عکس و فیلم</b>\n\n1) از منوی 💼 میز حرفه‌ای پشتیبانی یا فروش را شروع کنید\n2) متن را بفرستید\n3) بعد عکس یا فیلم را ارسال کنید\n4) در پایان /done بزنید\n\nادمین‌ها فایل را مستقیم دریافت می‌کنند."
                : "📎 <b>Send photo & video</b>\n\n1) Open 💼 Pro Desk → Support or Sales\n2) Send your text\n3) Then send photo or video\n4) Finish with /done\n\nAdmins receive media instantly.";
            Presenter::editOrSend($chatId, $msgId, $t, request_hub_keyboard($lang));
            return;
        }

        if ($data === 'help') {
            answer_callback($id);
            Presenter::editOrSend($chatId, $msgId, help_text($lang), main_keyboard($lang));
            return;
        }

        if ($data === 'lang') {
            answer_callback($id);
            $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
            Presenter::editOrSend($chatId, $msgId, language_gate_text(), lang_keyboard_world(false, 0, $detected));
            return;
        }

        if (strpos($data, 'langpage:') === 0) {
            $page = (int)substr($data, 9);
            answer_callback($id);
            $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
            Presenter::editOrSend($chatId, $msgId, language_gate_text(), lang_keyboard_world(true, $page, $detected));
            return;
        }

        if (strpos($data, 'startlang:') === 0 || strpos($data, 'setlang:') === 0) {
            $fromStart = (strpos($data, 'startlang:') === 0);
            $code = $fromStart ? substr($data, 10) : substr($data, 8);
            $code = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string)$code) ?: 'en');
            if ($code === '') {
                $code = 'en';
            }
            // Validate against active languages; unknown → English
            try {
                $st = db()->prepare('SELECT code FROM languages WHERE code=? AND is_active=1 LIMIT 1');
                $st->execute(array($code));
                if (!$st->fetchColumn()) {
                    $code = 'en';
                }
            } catch (\Throwable $e) {
            }
            answer_callback($id, '✅');
            UserRepository::setLang($userId, $code);
            $lang = $code;

            $opened = false;
            $hasHook = function_exists('do_action')
                && !empty($GLOBALS['hdd_actions']['after_language_selected']);
            if ($hasHook) {
                try {
                    do_action('after_language_selected', $chatId, $msgId, $userId, $lang);
                    $opened = true;
                } catch (\Throwable $e) {
                    @file_put_contents(
                        dirname(__DIR__, 2) . '/error.log',
                        date('c') . ' after_language_selected: ' . $e->getMessage() . "\n",
                        FILE_APPEND
                    );
                }
            }
            if (!$opened) {
                $hub = function_exists('graphical_main_hub') ? graphical_main_hub($lang) : main_keyboard($lang);
                Presenter::editOrSend(
                    $chatId,
                    $msgId,
                    ($lang === 'fa' ? "✅ زبان تنظیم شد.\n\n" : "✅ Language set.\n\n") . welcome_text($lang),
                    $hub
                );
                // SmartI18n hook already sends the reply keyboard — only send here as fallback
                if (function_exists('main_reply_keyboard')) {
                    send_message(
                        $chatId,
                        $lang === 'fa' ? '⌨️ میانبرهای پایین صفحه:' : '⌨️ Bottom shortcuts:',
                        main_reply_keyboard($lang)
                    );
                }
            }
            return;
        }

        if ($data === 'main' || $data === 'menu:root') {
            answer_callback($id);
            Presenter::editOrSend(
                $chatId,
                $msgId,
                $lang === 'fa' ? '🏠 <b>منوی اصلی</b>' : '🏠 <b>Main Menu</b>',
                function_exists('graphical_main_hub') ? graphical_main_hub($lang) : main_keyboard($lang)
            );
            return;
        }

        if (strpos($data, 'menu:') === 0) {
            $mid = (int)substr($data, 5);
            answer_callback($id);
            if ($mid <= 0) {
                Presenter::editOrSend($chatId, $msgId, $lang === 'fa' ? '📑 <b>منو</b>' : '📑 <b>Menu</b>', main_keyboard($lang));
            } else {
                $m = FaqRepository::findMenu($mid);
                if ($m && function_exists('localize_menu_row')) {
                    $m = localize_menu_row($m, $lang);
                }
                $title = $m ? $m['title'] : 'Menu';
                Presenter::editOrSend($chatId, $msgId, '📑 <b>' . htmlspecialchars((string)$title) . '</b>', build_menu_keyboard($mid, $lang));
            }
            return;
        }

        if (strpos($data, 'menutxt:') === 0) {
            $mid = (int)substr($data, 8);
            answer_callback($id);
            $row = FaqRepository::findActiveMenu($mid);
            if ($row && function_exists('localize_menu_row')) {
                $row = localize_menu_row($row, $lang);
            }
            $text = $row ? ('<b>' . htmlspecialchars((string)$row['title']) . "</b>\n\n" . htmlspecialchars((string)$row['value_text'])) : 'Empty.';
            Presenter::editOrSend($chatId, $msgId, $text, main_keyboard($lang));
            return;
        }

        if (strpos($data, 'cmd:') === 0) {
            $cmd = substr($data, 4);
            answer_callback($id);
            if ($cmd === 'training') {
                Presenter::editOrSend($chatId, $msgId, ContentService::trainingText($lang), main_keyboard($lang));
            } elseif ($cmd === 'website') {
                Presenter::editOrSend($chatId, $msgId, ContentService::websiteText($lang), main_keyboard($lang));
            } elseif ($cmd === 'help') {
                Presenter::editOrSend($chatId, $msgId, help_text($lang), main_keyboard($lang));
            } elseif ($cmd === 'shop') {
                if (function_exists('feature_on') && !feature_on('shop')) {
                    Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'فروشگاه غیرفعال است.', 'Shop is disabled.'), main_keyboard($lang));
                } else {
                    ShopService::showList($chatId, $msgId, $lang);
                }
            } elseif ($cmd === 'forum') {
                if (function_exists('feature_on') && !feature_on('forum')) {
                    Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'فروم غیرفعال است.', 'Forum is disabled.'), main_keyboard($lang));
                } else {
                    ForumService::show($chatId, $msgId, $lang);
                }
            } elseif ($cmd === 'faq') {
                FaqService::showHub($chatId, $lang);
            } elseif ($cmd === 'lang') {
                $detected = function_exists('detect_telegram_lang') ? detect_telegram_lang($from) : 'en';
                Presenter::editOrSend($chatId, $msgId, language_gate_text(), lang_keyboard_world(false, 0, $detected));
            } elseif ($cmd === 'support') {
                if (function_exists('feature_on') && !feature_on('prodesk')) {
                    Presenter::editOrSend($chatId, $msgId, Presenter::featureDisabled($lang, 'میز حرفه‌ای غیرفعال است.', 'Pro Desk is disabled.'), main_keyboard($lang));
                } else {
                    show_request_hub($chatId, $msgId, $lang);
                }
            } else {
                Presenter::editOrSend(
                    $chatId,
                    $msgId,
                    $lang === 'fa' ? 'دستور ناشناخته.' : 'Unknown command.',
                    main_keyboard($lang)
                );
            }
            return;
        }

        if (strpos($data, 'faqcat:') === 0) {
            $cat = rawurldecode(substr($data, 7));
            answer_callback($id);
            FaqService::showCategory($chatId, $msgId, $cat, $lang);
            return;
        }

        if (strpos($data, 'faq:') === 0) {
            $fid = (int)substr($data, 4);
            answer_callback($id);
            FaqService::showOne($chatId, $msgId, $fid, $lang);
            return;
        }

        if (strpos($data, 'product:') === 0) {
            $pid = (int)substr($data, 8);
            answer_callback($id);
            ShopService::showProduct($chatId, $msgId, $pid);
            return;
        }

        $extras = array('cart','orders','checkout','license','renew','demo','profile','feedback','referral','contact','brands','news','miniapp','vipdl');
        if (in_array($data, $extras, true)) {
            answer_callback($id);
            \HddLand\Bot\Services\ExtraMenusService::show($data, $chatId, $msgId, $userId, $lang);
            return;
        }

        answer_callback($id);
        Presenter::editOrSend(
            $chatId,
            $msgId,
            $lang === 'fa'
                ? '⚠️ این دکمه فعلاً متصل نیست. از /menu استفاده کنید.'
                : '⚠️ This button is not wired yet. Use /menu.',
            main_keyboard($lang)
        );
    }
}
