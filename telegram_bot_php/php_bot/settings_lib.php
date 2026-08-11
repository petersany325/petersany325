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
        'ai_system_prompt' => 'You are an expert HDD repair and data recovery assistant for HDD-Land.com / SeDiv. Be concise and professional.',
        'ai_model' => 'gpt-4o-mini',
        'openai_api_key' => '',
        'weather_api_key' => '',
        'feature_shop' => 1,
        'feature_forum' => 1,
        'feature_faq' => 1,
        'feature_tickets' => 1,
        'feature_prodesk' => 1,
        'feature_ai' => 1,
        'feature_language_gate' => 1,
        'feature_auto_faq_search' => 1,
        'notify_tickets' => 1,
        'notify_requests' => 1,
        'notify_media' => 1,
        'start_with_menu' => 0,
        'maintenance_mode' => 0,
        'maintenance_text' => 'Bot is under maintenance. Please try again later.',
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
    // fallback other lang
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
        $merged = merge_bot_defaults_into_config($cfg);
        if ($merged !== $cfg && function_exists('save_bot_config')) {
            // Don't auto-write on every request — only fill missing keys in memory via bot_config static is hard.
            // Admin Settings "Initialize defaults" button writes them.
        }
        return $merged;
    } catch (Throwable $e) {
        return bot_defaults();
    }
}
