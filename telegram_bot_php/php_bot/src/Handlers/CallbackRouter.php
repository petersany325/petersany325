<?php
declare(strict_types=1);

namespace HddLand\Bot\Handlers;

use HddLand\Bot\Context;
use HddLand\Bot\Repositories\FaqRepository;
use HddLand\Bot\Repositories\UserRepository;
use HddLand\Bot\Services\ContentService;
use HddLand\Bot\Services\FaqService;
use HddLand\Bot\Services\ForumService;
use HddLand\Bot\Services\ShopService;
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
            start_request_flow($chatId, $userId, 'support', $lang);
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
            answer_callback($id, $code === 'fa' ? 'فارسی' : 'English');
            UserRepository::setLang($userId, $code);
            $lang = $code;
            if (function_exists('do_action')) {
                do_action('after_language_selected', $chatId, $msgId, $userId, $lang);
            } else {
                Presenter::editOrSend($chatId, $msgId, welcome_text($lang), function_exists('graphical_main_hub') ? graphical_main_hub($lang) : main_keyboard($lang));
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
            } else {
                Presenter::editOrSend($chatId, $msgId, help_text($lang), main_keyboard($lang));
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

        $extras = array('cart','orders','checkout','license','renew','demo','profile','feedback','referral','contact','brands','news','miniapp');
        if (in_array($data, $extras, true)) {
            answer_callback($id);
            \HddLand\Bot\Services\ExtraMenusService::show($data, $chatId, $msgId, $userId, $lang);
            return;
        }

        answer_callback($id, 'OK');
    }
}
