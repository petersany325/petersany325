<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

use HddLand\Bot\Support\Presenter;

/**
 * Professional menu surfaces controlled by Admin → Settings.
 */
final class ExtraMenusService
{
    public static function show(string $key, int $chatId, int $msgId, int $userId, string $lang): void
    {
        $feat = self::featureKey($key);
        if ($feat && function_exists('feature_on') && !feature_on($feat)) {
            Presenter::editOrSend(
                $chatId,
                $msgId,
                Presenter::featureDisabled($lang, 'این بخش فعلاً غیرفعال است.', 'This section is currently disabled.'),
                main_keyboard($lang)
            );
            return;
        }

        switch ($key) {
            case 'cart':
                self::page($chatId, $msgId, $lang, 'cart',
                    $lang === 'fa' ? '🛒 <b>سبد خرید</b>' : '🛒 <b>Your Cart</b>',
                    $lang === 'fa'
                        ? "محصولات SeDiv را از فروشگاه اضافه کنید.\nبرای خرید مستقیم از دکمه پرداخت/فروش استفاده کنید."
                        : "Add SeDiv products from the shop.\nUse Checkout / Sales to continue.");
                break;
            case 'orders':
                self::page($chatId, $msgId, $lang, 'orders',
                    $lang === 'fa' ? '📦 <b>سفارش‌های من</b>' : '📦 <b>My Orders</b>',
                    $lang === 'fa'
                        ? "هنوز سفارش ثبت‌شده‌ای ندارید.\nپس از خرید، وضعیت لایسنس اینجا نمایش داده می‌شود."
                        : "No saved orders yet.\nAfter purchase, license status appears here.");
                break;
            case 'checkout':
                self::checkout($chatId, $msgId, $lang);
                break;
            case 'license':
                self::page($chatId, $msgId, $lang, 'license',
                    $lang === 'fa' ? '🔑 <b>وضعیت لایسنس</b>' : '🔑 <b>License Status</b>',
                    (string)(cfg('license_help_text_' . ($lang === 'fa' ? 'fa' : 'en'), '')
                        ?: ($lang === 'fa'
                            ? "کد لایسنس SeDiv خود را برای پشتیبانی بفرستید یا از فروش درخواست تمدید کنید.\n\nUsage: پیام متنی به پشتیبانی یا /ticket"
                            : "Send your SeDiv license code to support, or request renewal via Sales.\n\nTip: /ticket your license question")));
                break;
            case 'renew':
                if (function_exists('start_request_flow')) {
                    start_request_flow($chatId, $userId, 'sales', $lang);
                    $extra = (string)cfg('renewal_message_' . ($lang === 'fa' ? 'fa' : 'en'), '');
                    if ($extra !== '') {
                        send_message($chatId, $extra);
                    }
                } else {
                    self::page($chatId, $msgId, $lang, 'renew',
                        $lang === 'fa' ? '♻️ <b>تمدید لایسنس</b>' : '♻️ <b>Renew License</b>',
                        $lang === 'fa' ? 'برای تمدید با فروش در ارتباط باشید.' : 'Contact sales to renew your license.');
                }
                break;
            case 'demo':
                if (function_exists('start_request_flow')) {
                    start_request_flow($chatId, $userId, 'sales', $lang);
                    $info = (string)cfg('demo_request_info_' . ($lang === 'fa' ? 'fa' : 'en'), '');
                    send_message($chatId, $info !== '' ? $info : ($lang === 'fa'
                        ? '▶️ درخواست دمو ثبت شد — مدل درایو و نیازتان را بنویسید.'
                        : '▶️ Demo request started — send drive model and your need.'));
                }
                break;
            case 'profile':
                $uname = '';
                $contact = '';
                $phone = '';
                try {
                    $st = db()->prepare('SELECT username, full_name, lang, contact_name, phone FROM users WHERE telegram_id=? LIMIT 1');
                    $st->execute([$userId]);
                    $u = $st->fetch() ?: [];
                    $uname = trim((string)($u['full_name'] ?? $u['username'] ?? ''));
                    $contact = trim((string)($u['contact_name'] ?? ''));
                    $phone = trim((string)($u['phone'] ?? ''));
                    $ulang = (string)($u['lang'] ?? $lang);
                } catch (\Throwable $e) {
                    $ulang = $lang;
                }
                $text = ($lang === 'fa' ? '👤 <b>پروفایل من</b>' : '👤 <b>My Profile</b>') . "\n\n"
                    . 'ID: <code>' . $userId . "</code>\n"
                    . 'Name: ' . htmlspecialchars($uname !== '' ? $uname : '-') . "\n"
                    . ($lang === 'fa' ? 'نام تماس: ' : 'Contact: ') . htmlspecialchars($contact !== '' ? $contact : '-') . "\n"
                    . ($lang === 'fa' ? 'تلفن: ' : 'Phone: ') . htmlspecialchars($phone !== '' ? $phone : '-') . "\n"
                    . 'Lang: ' . htmlspecialchars($ulang);
                Presenter::editOrSend($chatId, $msgId, $text, self::kb($lang, array(
                    array('mytickets', $lang === 'fa' ? '🎫 تیکت‌ها' : '🎫 Tickets'),
                    array('orders', $lang === 'fa' ? '📦 سفارش‌ها' : '📦 Orders'),
                    array('license', $lang === 'fa' ? '🔑 لایسنس' : '🔑 License'),
                    array('main', $lang === 'fa' ? '🏠 منو' : '🏠 Menu'),
                )));
                break;
            case 'feedback':
                if (function_exists('start_request_flow')) {
                    start_request_flow($chatId, $userId, 'support', $lang);
                    send_message($chatId, (string)(cfg('feedback_thankyou_' . ($lang === 'fa' ? 'fa' : 'en'), '')
                        ?: ($lang === 'fa' ? '⭐ نظر خود را بنویسید — ممنون از بازخورد شما.' : '⭐ Please write your feedback — thank you!')));
                }
                break;
            case 'referral':
                $link = 'https://t.me/' . ltrim((string)cfg('bot_username', 'HDDLandBot'), '@') . '?start=ref_' . $userId;
                $bonus = (string)cfg('referral_bonus_text_' . ($lang === 'fa' ? 'fa' : 'en'), '');
                $text = ($lang === 'fa' ? '🎁 <b>معرفی به دوست</b>' : '🎁 <b>Referral</b>') . "\n\n"
                    . ($bonus !== '' ? $bonus . "\n\n" : '')
                    . ($lang === 'fa' ? 'لینک شما:' : 'Your link:') . "\n<code>{$link}</code>";
                Presenter::editOrSend($chatId, $msgId, $text, self::kb($lang, array(
                    array('main', $lang === 'fa' ? '🏠 منو' : '🏠 Menu'),
                )));
                break;
            case 'contact':
                $phone = trim((string)cfg('contact_phone', ''));
                $hours = trim((string)cfg('contact_hours', ''));
                $email = trim((string)cfg('support_email', ''));
                $text = ($lang === 'fa' ? '☎️ <b>تماس با انسان</b>' : '☎️ <b>Contact a Human</b>') . "\n\n";
                if ($phone !== '') {
                    $text .= ($lang === 'fa' ? '📞 تلفن: ' : '📞 Phone: ') . htmlspecialchars($phone) . "\n";
                }
                if ($hours !== '') {
                    $text .= ($lang === 'fa' ? '🕒 ساعت: ' : '🕒 Hours: ') . htmlspecialchars($hours) . "\n";
                }
                if ($email !== '') {
                    $text .= '✉ ' . htmlspecialchars($email) . "\n";
                }
                $text .= "\n" . ($lang === 'fa' ? 'یا همین‌جا پشتیبانی را شروع کنید.' : 'Or start support chat here.');
                Presenter::editOrSend($chatId, $msgId, $text, self::kb($lang, array(
                    array('req:support', $lang === 'fa' ? '🛠️ پشتیبانی' : '🛠️ Support'),
                    array('main', $lang === 'fa' ? '🏠 منو' : '🏠 Menu'),
                )));
                break;
            case 'brands':
                $prompt = (string)cfg('brand_search_prompt_' . ($lang === 'fa' ? 'fa' : 'en'), '');
                $text = ($lang === 'fa' ? '🔧 <b>جستجوی برند</b>' : '🔧 <b>Brand Search</b>') . "\n\n"
                    . ($prompt !== '' ? $prompt : ($lang === 'fa'
                        ? "برند را انتخاب کنید یا در انجمن جستجو کنید:"
                        : "Pick a brand or search the forum:"));
                $forum = (string)bot_config()['forum_url'];
                $kb = array(
                    'inline_keyboard' => array(
                        array(
                            array('text' => 'WD', 'url' => $forum),
                            array('text' => 'Seagate', 'url' => $forum),
                        ),
                        array(
                            array('text' => 'Toshiba', 'url' => $forum),
                            array('text' => 'Samsung', 'url' => $forum),
                        ),
                        array(
                            array('text' => 'Hitachi / HGST', 'url' => $forum),
                            array('text' => 'Fujitsu', 'url' => $forum),
                        ),
                        array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main')),
                    ),
                );
                Presenter::editOrSend($chatId, $msgId, $text, $kb);
                break;
            case 'news':
                $url = trim((string)cfg('news_channel_url', ''));
                $text = ($lang === 'fa' ? '📰 <b>اخبار و آپدیت</b>' : '📰 <b>News & Updates</b>') . "\n\n"
                    . ($lang === 'fa' ? 'آخرین اخبار SeDiv و HDD-Land.' : 'Latest SeDiv and HDD-Land updates.');
                $rows = array();
                if ($url !== '') {
                    $rows[] = array(array('text' => $lang === 'fa' ? '📣 کانال اخبار' : '📣 News Channel', 'url' => $url));
                }
                $rows[] = array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main'));
                Presenter::editOrSend($chatId, $msgId, $text, array('inline_keyboard' => $rows));
                break;
            case 'miniapp':
                $url = trim((string)cfg('miniapp_url', ''));
                if ($url === '') {
                    Presenter::editOrSend($chatId, $msgId,
                        $lang === 'fa' ? 'Mini App هنوز در تنظیمات ست نشده.' : 'Mini App URL is not configured in Settings.',
                        main_keyboard($lang));
                    return;
                }
                Presenter::editOrSend($chatId, $msgId,
                    $lang === 'fa' ? '📱 <b>Mini App</b>\n\nفروشگاه پیشرفته داخل تلگرام:' : '📱 <b>Mini App</b>\n\nOpen the advanced storefront:',
                    array('inline_keyboard' => array(
                        array(array('text' => $lang === 'fa' ? '🚀 باز کردن' : '🚀 Open', 'url' => $url)),
                        array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main')),
                    )));
                break;
            default:
                Presenter::editOrSend($chatId, $msgId, 'Unknown menu.', main_keyboard($lang));
        }
    }

    private static function checkout(int $chatId, int $msgId, string $lang): void
    {
        $url = trim((string)cfg('checkout_url', ''));
        $token = trim((string)cfg('payment_provider_token', ''));
        $text = ($lang === 'fa' ? '💳 <b>پرداخت / Checkout</b>' : '💳 <b>Checkout</b>') . "\n\n";
        if ($token !== '') {
            $text .= $lang === 'fa'
                ? "پرداخت تلگرام آماده است. از فروشگاه محصول را انتخاب کنید یا با فروش ادامه دهید."
                : "Telegram Payments token is configured. Pick a product in Shop or continue with Sales.";
        } elseif ($url !== '') {
            $text .= $lang === 'fa' ? 'برای تکمیل خرید روی دکمه زیر بزنید.' : 'Continue checkout with the button below.';
        } else {
            $text .= $lang === 'fa'
                ? "درگاه هنوز کامل نشده — درخواست خرید برای تیم فروش ارسال می‌شود."
                : "Gateway not fully configured — continue with a sales request.";
        }
        $rows = array();
        if ($url !== '') {
            $rows[] = array(array('text' => $lang === 'fa' ? '🌐 صفحه پرداخت' : '🌐 Checkout page', 'url' => $url));
        }
        $rows[] = array(array('text' => $lang === 'fa' ? '💎 درخواست خرید' : '💎 Sales Request', 'callback_data' => 'req:sales'));
        $rows[] = array(array('text' => $lang === 'fa' ? '🛒 فروشگاه' : '🛒 Shop', 'callback_data' => 'shop'));
        $rows[] = array(array('text' => $lang === 'fa' ? '🏠 منو' : '🏠 Menu', 'callback_data' => 'main'));
        Presenter::editOrSend($chatId, $msgId, $text, array('inline_keyboard' => $rows));
    }

    private static function page(int $chatId, int $msgId, string $lang, string $key, string $title, string $body): void
    {
        $custom = function_exists('content_text') ? content_text($key, $lang) : null;
        $text = $title . "\n\n" . ($custom ?: $body);
        Presenter::editOrSend($chatId, $msgId, $text, self::kb($lang, array(
            array('shop', $lang === 'fa' ? '🛒 فروشگاه' : '🛒 Shop'),
            array('req:sales', $lang === 'fa' ? '💎 فروش' : '💎 Sales'),
            array('main', $lang === 'fa' ? '🏠 منو' : '🏠 Menu'),
        )));
    }

    /** @param list<array{0:string,1:string}> $buttons */
    private static function kb(string $lang, array $buttons): array
    {
        $rows = array();
        $row = array();
        foreach ($buttons as $b) {
            $row[] = array('text' => $b[1], 'callback_data' => $b[0]);
            if (count($row) === 2) {
                $rows[] = $row;
                $row = array();
            }
        }
        if ($row) {
            $rows[] = $row;
        }
        return array('inline_keyboard' => $rows);
    }

    private static function featureKey(string $key): ?string
    {
        $map = array(
            'cart' => 'cart',
            'orders' => 'orders',
            'checkout' => 'payments',
            'license' => 'license',
            'renew' => 'renewal',
            'demo' => 'demo',
            'profile' => 'profile',
            'feedback' => 'feedback',
            'referral' => 'referral',
            'contact' => 'contact',
            'brands' => 'brand_search',
            'news' => 'news',
            'miniapp' => 'miniapp',
            'mytickets' => 'tickets',
        );
        return $map[$key] ?? null;
    }
}
