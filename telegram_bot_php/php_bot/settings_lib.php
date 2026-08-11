<?php
/**
 * Central bot settings helpers — all runtime options come from config.local.php
 * (edited only via Admin → Settings).
 */
declare(strict_types=1);

function bot_defaults() {
    return array(
        'site_url' => 'https://hdd-land.com',
        'forum_url' => 'https://hdd-land.com/forum',
        'training_url' => 'https://hdd-land.com/forum',
        'support_email' => '',
        'sales_email' => '',
        'bot_title' => 'HDD-Land Bot',
        'bot_subtitle' => 'SeDiv Professional · Data Recovery',
        'bot_username' => '',
        'gate_text' => '',
        'welcome_text_en' => '',
        'welcome_text_fa' => '',
        'website_text_en' => '',
        'website_text_fa' => '',
        'forum_text_en' => '',
        'forum_text_fa' => '',
        'training_text_en' => '',
        'training_text_fa' => '',
        'shop_text_en' => '',
        'shop_text_fa' => '',
        'help_text_en' => '',
        'help_text_fa' => '',
        'cart_text_en' => '',
        'cart_text_fa' => '',
        'orders_text_en' => '',
        'orders_text_fa' => '',
        'license_text_en' => '',
        'license_text_fa' => '',
        'ai_system_prompt' => 'You are an expert HDD repair and data recovery assistant for HDD-Land.com / SeDiv. Be concise and professional.',
        'ai_model' => 'gpt-4o-mini',
        'openai_api_key' => '',
        'weather_api_key' => '',

        // Core modules
        'feature_shop' => 1,
        'feature_forum' => 1,
        'feature_faq' => 1,
        'feature_tickets' => 1,
        'feature_prodesk' => 1,
        'feature_ai' => 1,
        'feature_language_gate' => 1,
        'feature_auto_faq_search' => 1,

        // Professional modules
        'feature_cart' => 1,
        'feature_orders' => 1,
        'feature_payments' => 1,
        'feature_license' => 1,
        'feature_renewal' => 1,
        'feature_demo' => 1,
        'feature_profile' => 1,
        'feature_feedback' => 1,
        'feature_referral' => 1,
        'feature_contact' => 1,
        'feature_brand_search' => 1,
        'feature_news' => 1,
        'feature_miniapp' => 0,

        'notify_tickets' => 1,
        'notify_requests' => 1,
        'notify_media' => 1,
        'notify_orders' => 1,
        'notify_feedback' => 1,

        'start_with_menu' => 0,
        'maintenance_mode' => 0,
        'maintenance_text' => 'Bot is under maintenance. Please try again later.',

        // Commerce / payments
        'payment_provider_token' => '',
        'payment_currency' => 'USD',
        'checkout_url' => '',
        'miniapp_url' => '',

        // License / renewal
        'license_help_text_en' => '',
        'license_help_text_fa' => '',
        'license_check_url' => '',
        'renewal_days_before' => 14,
        'renewal_message_en' => 'Your SeDiv license may need renewal soon. Reply with your license code.',
        'renewal_message_fa' => 'ممکن است لایسنس SeDiv شما نزدیک تمدید باشد. کد لایسنس را بفرستید.',

        // Growth / contact
        'referral_bonus_text_en' => 'Share your link. When friends buy SeDiv, our team can apply your referral bonus.',
        'referral_bonus_text_fa' => 'لینک خود را به اشتراک بگذارید. با خرید دوستانتان، پاداش معرفی بررسی می‌شود.',
        'contact_phone' => '',
        'contact_hours' => 'Sat–Thu 10:00–19:00',
        'news_channel_url' => '',
        'demo_request_info_en' => '',
        'demo_request_info_fa' => '',
        'feedback_thankyou_en' => '',
        'feedback_thankyou_fa' => '',
        'brand_search_prompt_en' => '',
        'brand_search_prompt_fa' => '',

        // Integrations
        'crm_webhook_url' => '',
        'analytics_webhook_url' => '',

        // Advanced Technical Support + Tickets
        'support_intro_en' => "🛠️ <b>Advanced Technical Support</b>\n\nA few short questions help our team respond faster.\nCancel: /cancel",
        'support_intro_fa' => "🛠️ <b>پشتیبانی فنی پیشرفته</b>\n\nچند سؤال کوتاه می‌پرسیم تا تیم دقیق‌تر کمک کند.\nلغو: /cancel",
        'support_links' => "Forum|https://hdd-land.com/forum\nWebsite|https://hdd-land.com",
        'support_questions' => "drive_model|Hard drive model (e.g. WD20EFRX)|مدل هارد (مثلاً WD20EFRX)|1\nerror|Error / symptom|خطا / علائم مشکل|1\nsediv_version|SeDiv version (if any)|نسخه SeDiv (اگر دارید)|0",
        'ticket_ask_name' => 1,
        'ticket_ask_phone' => 1,
        'ticket_always_ask_name' => 0,
        'ticket_always_ask_phone' => 0,
        // Optional: require phone again to view tickets (default OFF — Telegram ID is enough)
        'ticket_phone_for_view' => 0,
    );
}

/** Get config value with default */
function cfg($key, $default = null) {
    $c = bot_config();
    if (array_key_exists($key, $c) && $c[$key] !== null && $c[$key] !== '') {
        return $c[$key];
    }
    $d = bot_defaults();
    if (array_key_exists($key, $d)) {
        return $d[$key];
    }
    return $default;
}

function feature_on($name) {
    $key = (strpos($name, 'feature_') === 0) ? $name : ('feature_' . $name);
    $v = cfg($key, 1);
    return (int)$v === 1 || $v === true || $v === '1';
}

function notify_on($name) {
    $key = (strpos($name, 'notify_') === 0) ? $name : ('notify_' . $name);
    $v = cfg($key, 1);
    return (int)$v === 1 || $v === true || $v === '1';
}

function merge_bot_defaults_into_config(array $cfg) {
    foreach (bot_defaults() as $k => $v) {
        if (!array_key_exists($k, $cfg)) {
            $cfg[$k] = $v;
        }
    }
    return $cfg;
}

/** Localized content page from settings */
function content_text($baseKey, $lang = 'en') {
    $lang = $lang === 'fa' ? 'fa' : 'en';
    $custom = cfg($baseKey . '_text_' . $lang, '');
    if (is_string($custom) && trim($custom) !== '') {
        return $custom;
    }
    $other = $lang === 'fa' ? 'en' : 'fa';
    $alt = cfg($baseKey . '_text_' . $other, '');
    if (is_string($alt) && trim($alt) !== '') {
        return $alt;
    }
    return null;
}

function staff_notify_ids() {
    $ids = array();
    $cfg = bot_config();
    if (!empty($cfg['admin_ids']) && is_array($cfg['admin_ids'])) {
        foreach ($cfg['admin_ids'] as $id) {
            $ids[] = (int)$id;
        }
    }
    try {
        if (function_exists('db')) {
            $rows = db()->query('SELECT telegram_id FROM staff_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $id) {
                $ids[] = (int)$id;
            }
        }
    } catch (Throwable $e) {}
    return array_values(array_unique(array_filter($ids)));
}

function notify_staff($text, $kind = 'tickets') {
    if (!notify_on($kind)) {
        return;
    }
    foreach (staff_notify_ids() as $adminId) {
        try {
            send_message($adminId, $text);
        } catch (Throwable $e) {}
    }
}

function ensure_settings_defaults_saved() {
    try {
        $cfg = bot_config();
        return merge_bot_defaults_into_config($cfg);
    } catch (Throwable $e) {
        return bot_defaults();
    }
}

/** All feature flag keys (without feature_ prefix) */
function all_feature_keys() {
    return array(
        'shop', 'forum', 'faq', 'tickets', 'prodesk', 'ai', 'language_gate', 'auto_faq_search',
        'cart', 'orders', 'payments', 'license', 'renewal', 'demo', 'profile', 'feedback',
        'referral', 'contact', 'brand_search', 'news', 'miniapp',
    );
}
