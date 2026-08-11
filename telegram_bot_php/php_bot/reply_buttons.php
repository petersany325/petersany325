<?php
/**
 * Persistent reply-keyboard (bottom buttons) + text → action mapping.
 * Telegram sends these as plain messages (not callbacks).
 */
declare(strict_types=1);

function main_reply_keyboard($lang = 'en') {
    $fa = ($lang === 'fa');
    $rows = array(
        array(
            array('text' => $fa ? '🛒 خرید SeDiv' : '🛒 Buy SEDIV'),
            array('text' => $fa ? '♻️ تمدید لایسنس' : '♻️ Renew Licence'),
        ),
        array(
            array('text' => $fa ? '🔧 پشتیبانی فنی' : '🔧 Technical Support'),
        ),
        array(
            array('text' => $fa ? '📦 سفارش‌های من' : '📦 My Orders'),
            array('text' => $fa ? '🎫 تیکت‌های من' : '🎫 My Tickets'),
        ),
        array(
            array('text' => $fa ? '💎 تماس فروش' : '💬 Contact Sales'),
            array('text' => $fa ? 'ℹ️ راهنما' : 'ℹ️ Help'),
        ),
    );
    return array(
        'keyboard' => $rows,
        'resize_keyboard' => true,
        'is_persistent' => true,
    );
}

/** Normalize button / free-text for matching */
function reply_button_norm($text) {
    $t = trim(preg_replace('/\s+/u', ' ', (string)$text));
    $plain = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $t);
    $plain = trim(preg_replace('/\s+/u', ' ', (string)$plain));
    // unify Persian/Arabic yeh/kaf variants lightly
    $plain = str_replace(array('ي', 'ك', '‌'), array('ی', 'ک', ''), $plain);
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($plain, 'UTF-8');
    }
    return strtolower($plain);
}

/**
 * Map reply-keyboard / free text to an action key, or null.
 * @return string|null
 */
function resolve_reply_button_action($text) {
    $n = reply_button_norm($text);
    if ($n === '') {
        return null;
    }

    $map = array(
        'my tickets' => 'mytickets',
        'my ticket' => 'mytickets',
        'tickets' => 'mytickets',
        'تیکت های من' => 'mytickets',
        'تیکتهای من' => 'mytickets',
        'تیکت من' => 'mytickets',
        'technical support' => 'support_form',
        'tech support' => 'support_form',
        'support' => 'support_form',
        'پشتیبانی فنی' => 'support_form',
        'پشتیبانی' => 'support_form',
        'buy sediv' => 'shop',
        'خرید sediv' => 'shop',
        'shop' => 'shop',
        'فروشگاه' => 'shop',
        'renew licence' => 'renew',
        'renew license' => 'renew',
        'تمدید لایسنس' => 'renew',
        'تمدید' => 'renew',
        'my orders' => 'orders',
        'orders' => 'orders',
        'سفارش های من' => 'orders',
        'سفارشهای من' => 'orders',
        'contact sales' => 'sales',
        'sales' => 'sales',
        'تماس فروش' => 'sales',
        'help' => 'help',
        'راهنما' => 'help',
        'menu' => 'menu',
        'منو' => 'menu',
        'main menu' => 'menu',
    );

    if (isset($map[$n])) {
        return $map[$n];
    }

    if (strpos($n, 'my ticket') !== false || (strpos($n, 'تیکت') !== false && strpos($n, 'من') !== false)) {
        return 'mytickets';
    }
    if (strpos($n, 'technical support') !== false || strpos($n, 'پشتیبانی فنی') !== false) {
        return 'support_form';
    }
    if (strpos($n, 'buy sediv') !== false || (strpos($n, 'خرید') !== false && stripos($n, 'sediv') !== false)) {
        return 'shop';
    }
    if (strpos($n, 'renew licen') !== false || strpos($n, 'تمدید') !== false) {
        return 'renew';
    }
    if (strpos($n, 'contact sales') !== false || strpos($n, 'تماس فروش') !== false) {
        return 'sales';
    }
    if (strpos($n, 'my order') !== false || (strpos($n, 'سفارش') !== false && strpos($n, 'من') !== false)) {
        return 'orders';
    }

    return null;
}

/**
 * Dispatch a reply-button action. Returns true if handled.
 */
function dispatch_reply_button_action($action, $chatId, $userId, $lang) {
    switch ($action) {
        case 'mytickets':
            if (function_exists('feature_on') && !feature_on('tickets') && !feature_on('prodesk')) {
                send_message($chatId, $lang === 'fa' ? 'تیکت غیرفعال است.' : 'Tickets are disabled.', main_reply_keyboard($lang));
                return true;
            }
            \HddLand\Bot\Services\SupportFormService::showMyTickets((int)$chatId, (int)$userId, (string)$lang);
            return true;
        case 'support_form':
            if (function_exists('feature_on') && !feature_on('tickets') && !feature_on('prodesk')) {
                send_message($chatId, $lang === 'fa' ? 'پشتیبانی غیرفعال است.' : 'Support is disabled.', main_reply_keyboard($lang));
                return true;
            }
            \HddLand\Bot\Services\SupportFormService::start((int)$chatId, (int)$userId, (string)$lang, 'support');
            return true;
        case 'shop':
            if (function_exists('feature_on') && !feature_on('shop')) {
                send_message($chatId, $lang === 'fa' ? 'فروشگاه غیرفعال است.' : 'Shop is disabled.', main_reply_keyboard($lang));
                return true;
            }
            \HddLand\Bot\Services\ShopService::showList((int)$chatId, 0, (string)$lang);
            return true;
        case 'renew':
            \HddLand\Bot\Services\ExtraMenusService::show('renew', (int)$chatId, 0, (int)$userId, (string)$lang);
            return true;
        case 'orders':
            \HddLand\Bot\Services\ExtraMenusService::show('orders', (int)$chatId, 0, (int)$userId, (string)$lang);
            return true;
        case 'sales':
            if (function_exists('feature_on') && !feature_on('prodesk')) {
                send_message($chatId, $lang === 'fa' ? 'فروش غیرفعال است.' : 'Sales is disabled.', main_reply_keyboard($lang));
                return true;
            }
            if (function_exists('start_request_flow')) {
                start_request_flow((int)$chatId, (int)$userId, 'sales', (string)$lang);
            } else {
                show_request_hub((int)$chatId, 0, (string)$lang);
            }
            return true;
        case 'help':
            send_message($chatId, help_text($lang), main_keyboard($lang));
            return true;
        case 'menu':
            send_message($chatId, $lang === 'fa' ? '📑 <b>منوی اصلی</b>' : '📑 <b>Main Menu</b>', main_reply_keyboard($lang));
            send_message(
                $chatId,
                $lang === 'fa' ? 'از دکمه‌های شیشه‌ای زیر هم می‌توانید استفاده کنید:' : 'You can also use the menu buttons below:',
                main_keyboard($lang)
            );
            return true;
        default:
            return false;
    }
}
