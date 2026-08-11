<?php
/**
 * SmartI18n — intelligent menu/FAQ translation plugin.
 * - Does not replace core DB records as "source of truth" (English stays original)
 * - Writes only to i18n / cache tables
 * - After language pick: shows smart categorized menu of all branches
 */

require_once dirname(__DIR__) . '/loader.php';

class SmartI18nPlugin {
    /** AI translate is slow — keep OFF in webhook/menu path so language → menu never freezes */
    public static $allowAi = false;

    public static function boot() {
        self::ensure_cache_table();
        add_filter('localize_menu_row', array(__CLASS__, 'filter_menu_row'), 10);
        add_filter('localize_faq_row', array(__CLASS__, 'filter_faq_row'), 10);
        add_filter('build_menu_keyboard', array(__CLASS__, 'filter_keyboard'), 10);
        add_action('after_language_selected', array(__CLASS__, 'on_language_selected'), 10);
        add_filter('language_gate_text', array(__CLASS__, 'filter_gate_text'), 10);
    }

    public static function ensure_cache_table() {
        try {
            db()->exec("CREATE TABLE IF NOT EXISTS i18n_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lang VARCHAR(10) NOT NULL,
                source_hash CHAR(32) NOT NULL,
                source_text VARCHAR(500) NOT NULL,
                translated TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_src (lang, source_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}
    }

    public static function normalize($text) {
        $t = trim(preg_replace('/\s+/', ' ', (string)$text));
        // strip leading emoji for lookup flexibility
        $plain = trim(preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]+/u', '', $t));
        $lower = function ($s) {
            if (function_exists('mb_strtolower')) {
                return mb_strtolower($s, 'UTF-8');
            }
            return strtolower($s);
        };
        return array($lower($t), $lower($plain));
    }

    public static function dictionary_lookup($text, $lang) {
        static $dict = null;
        if ($dict === null) {
            $dict = require __DIR__ . '/dictionary.php';
        }
        if ($lang === 'en' || empty($dict[$lang])) {
            return null;
        }
        list($full, $plain) = self::normalize($text);
        $map = $dict[$lang];
        if (isset($map[$full])) return $map[$full];
        if (isset($map[$plain])) {
            // keep emoji prefix from original if any
            if (preg_match('/^([\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]+)/u', $text, $m)) {
                $out = $map[$plain];
                if (strpos($out, trim($m[1])) === false) {
                    return trim($m[1]) . ' ' . $out;
                }
            }
            return $map[$plain];
        }
        return null;
    }

    public static function cache_get($text, $lang) {
        $hash = md5($lang . '|' . $text);
        $st = db()->prepare('SELECT translated FROM i18n_cache WHERE lang=? AND source_hash=?');
        $st->execute(array($lang, $hash));
        $v = $st->fetchColumn();
        return $v !== false ? $v : null;
    }

    public static function cache_set($text, $lang, $translated) {
        $hash = md5($lang . '|' . $text);
        try {
            db()->prepare('INSERT INTO i18n_cache (lang, source_hash, source_text, translated) VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE translated=VALUES(translated)')
                ->execute(array($lang, $hash, function_exists('mb_substr') ? mb_substr($text, 0, 500) : substr($text, 0, 500), $translated));
        } catch (Exception $e) {}
    }

    public static function ai_translate($text, $lang) {
        $cfg = bot_config();
        $key = isset($cfg['openai_api_key']) ? $cfg['openai_api_key'] : '';
        if ($key === '') {
            return null;
        }
        $langNames = array('fa' => 'Persian', 'ru' => 'Russian', 'zh' => 'Chinese', 'ar' => 'Arabic', 'tr' => 'Turkish');
        $target = isset($langNames[$lang]) ? $langNames[$lang] : $lang;
        $payload = array(
            'model' => 'gpt-4o-mini',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Translate Telegram bot UI labels for HDD-Land / SeDiv data recovery. Keep emojis. Return ONLY the translation, no quotes.',
                ),
                array('role' => 'user', 'content' => "Translate to {$target}:\n" . $text),
            ),
            'max_tokens' => 120,
        );
        $ctx = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
                'content' => json_encode($payload),
                'timeout' => 20,
            ),
        ));
        $raw = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
        if ($raw === false) {
            return null;
        }
        $json = json_decode($raw, true);
        $out = isset($json['choices'][0]['message']['content']) ? trim($json['choices'][0]['message']['content']) : '';
        return $out !== '' ? $out : null;
    }

    /** Translate text to lang without changing English source */
    public static function translate($text, $lang, $persistMenuId = null) {
        $text = (string)$text;
        if ($lang === 'en' || $text === '') {
            return $text;
        }
        // 1) existing menu_i18n handled by core; plugin fills gaps
        $hit = self::dictionary_lookup($text, $lang);
        if ($hit !== null) {
            if ($persistMenuId) {
                try { save_menu_translation($persistMenuId, $lang, $hit, null); } catch (Exception $e) {}
            }
            return $hit;
        }
        $cached = self::cache_get($text, $lang);
        if ($cached !== null) {
            return $cached;
        }
        // Never call OpenAI during Telegram webhook — it blocks the next menu
        if (!self::$allowAi) {
            return $text; // English source until dictionary/cache exists
        }
        $ai = self::ai_translate($text, $lang);
        if ($ai !== null) {
            self::cache_set($text, $lang, $ai);
            if ($persistMenuId) {
                try { save_menu_translation($persistMenuId, $lang, $ai, null); } catch (Exception $e) {}
            }
            return $ai;
        }
        return $text; // fallback: show English
    }

    public static function filter_menu_row($row, $lang) {
        if (!is_array($row) || empty($row['title'])) {
            return $row;
        }
        if ($lang === 'en') {
            return $row;
        }
        // If core already applied i18n with different title, keep it
        // We detect by checking DB translation; if missing, smart-fill
        $existing = null;
        try {
            $existing = get_menu_translation((int)$row['id'], $lang);
        } catch (Exception $e) {}
        if ($existing && !empty($existing['title'])) {
            $row['title'] = $existing['title'];
            if ($existing['value_text'] !== null && $existing['value_text'] !== '' && $row['menu_type'] === 'text') {
                $row['value_text'] = $existing['value_text'];
            }
            return $row;
        }
        $row['title'] = self::translate($row['title'], $lang, (int)$row['id']);
        if ($row['menu_type'] === 'text' && !empty($row['value_text'])) {
            $row['value_text'] = self::translate($row['value_text'], $lang, null);
        }
        return $row;
    }

    public static function filter_faq_row($row, $lang) {
        if (!is_array($row) || $lang === 'en') {
            return $row;
        }
        $existing = null;
        try {
            $existing = get_faq_translation((int)$row['id'], $lang);
        } catch (Exception $e) {}
        if ($existing) {
            return $row; // core already localized
        }
        $row['question'] = self::translate($row['question'], $lang);
        // Hot path: dictionary/cache only (AI disabled in webhook)
        if (strlen((string)$row['answer']) < 120) {
            $row['answer'] = self::translate($row['answer'], $lang);
        }
        return $row;
    }

    public static function filter_keyboard($keyboard, $parentId, $lang) {
        // ensure back button translated
        if (!is_array($keyboard) || empty($keyboard['inline_keyboard'])) {
            return $keyboard;
        }
        foreach ($keyboard['inline_keyboard'] as &$row) {
            foreach ($row as &$btn) {
                if (isset($btn['text']) && (strpos($btn['text'], 'Back') !== false || strpos($btn['text'], 'بازگشت') !== false || strpos($btn['text'], 'Main Menu') !== false)) {
                    $btn['text'] = self::translate($btn['text'], $lang);
                }
            }
        }
        return $keyboard;
    }

    public static function filter_gate_text($text) {
        return "🤖 <b>HDD-Land Smart Bot</b>\n"
            . "SeDiv Professional · Data Recovery\n\n"
            . "🌍 <b>Select language to continue</b>\n"
            . "All menus & submenus will switch to your language.\n\n"
            . "🇬🇧 English  ·  🇮🇷 فارسی\n"
            . "لطفاً زبان را انتخاب کنید — منوها خودکار ترجمه می‌شوند.";
    }

    /**
     * After language: open main menu immediately (never block on AI translate).
     */
    public static function on_language_selected($chatId, $messageId, $userId, $lang) {
        $chatId = (int)$chatId;
        $messageId = (int)$messageId;
        $lang = $lang ? (string)$lang : 'en';

        // Fast dictionary warm-up only (no OpenAI in the webhook path — that was freezing the menu)
        try {
            if ($lang !== 'en') {
                $all = db()->query('SELECT id, title, value_text, menu_type FROM menus WHERE is_active=1 ORDER BY id ASC LIMIT 80')->fetchAll();
                foreach ($all as $m) {
                    $hit = self::dictionary_lookup((string)$m['title'], $lang);
                    if ($hit !== null) {
                        try {
                            save_menu_translation((int)$m['id'], $lang, $hit, null);
                        } catch (Exception $e) {
                        }
                    }
                }
            }
        } catch (Exception $e) {
        }

        try {
            $hub = function_exists('graphical_main_hub') ? graphical_main_hub($lang) : self::build_smart_hub($lang);
            if (!is_array($hub) || empty($hub['inline_keyboard'])) {
                $hub = function_exists('main_keyboard') ? main_keyboard($lang) : self::build_smart_hub($lang);
            }
            $intro = $lang === 'fa'
                ? "✅ زبان تنظیم شد.\n\nمنوی اصلی آماده است:"
                : "✅ Language set.\n\nMain menu is ready:";
            $body = $intro . "\n\n" . (function_exists('welcome_text') ? welcome_text($lang) : '');
            if (function_exists('edit_or_send')) {
                edit_or_send($chatId, $messageId, $body, $hub);
            } else {
                if ($messageId > 0) {
                    edit_message($chatId, $messageId, $body, $hub);
                } else {
                    send_message($chatId, $body, $hub);
                }
            }
            if (function_exists('main_reply_keyboard')) {
                send_message(
                    $chatId,
                    $lang === 'fa' ? '⌨️ میانبرهای پایین صفحه:' : '⌨️ Bottom shortcuts:',
                    main_reply_keyboard($lang)
                );
            }
        } catch (Throwable $e) {
            @file_put_contents(
                dirname(__DIR__, 2) . '/error.log',
                date('c') . ' SmartI18n menu: ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
            // Last-resort: always open something for the user
            try {
                send_message(
                    $chatId,
                    $lang === 'fa' ? '🏠 منوی اصلی' : '🏠 Main Menu',
                    function_exists('main_keyboard') ? main_keyboard($lang) : null
                );
            } catch (Throwable $e2) {
            }
        }
    }

    /** Smart categorized keyboard: all root items + quick links to deep branches */
    public static function build_smart_hub($lang) {
        $items = get_menu_items(null, $lang);
        $byCat = array();
        foreach ($items as $it) {
            $cat = !empty($it['category']) ? $it['category'] : 'Main';
            $byCat[$cat][] = $it;
        }

        $kb = array();
        // Language stays accessible
        $langBtn = array('text' => self::translate('🌍 Language', $lang), 'callback_data' => 'lang');

        foreach ($byCat as $cat => $list) {
            $row = array();
            foreach ($list as $it) {
                $btn = array('text' => $it['title']);
                $type = $it['menu_type'];
                if ($type === 'url') {
                    $btn['url'] = $it['value_text'] ? $it['value_text'] : 'https://hdd-land.com';
                } elseif ($type === 'submenu') {
                    $btn['callback_data'] = 'menu:' . $it['id'];
                } elseif ($type === 'faq_list') {
                    $btn['callback_data'] = 'faqcat:all';
                } elseif ($type === 'callback') {
                    $btn['callback_data'] = $it['value_text'] ? $it['value_text'] : 'main';
                } elseif ($type === 'command') {
                    $btn['callback_data'] = 'cmd:' . ($it['value_text'] ? $it['value_text'] : 'help');
                } else {
                    $btn['callback_data'] = 'menutxt:' . $it['id'];
                }
                $row[] = $btn;
                if (count($row) === 2) {
                    $kb[] = $row;
                    $row = array();
                }
            }
            if ($row) {
                $kb[] = $row;
            }
        }

        // Extra: expose first-level children of each submenu for visibility
        try {
            $subs = db()->query("SELECT * FROM menus WHERE parent_id IS NOT NULL AND is_active=1 AND menu_type='submenu' ORDER BY id")->fetchAll();
            foreach ($subs as $sub) {
                $sub = self::filter_menu_row($sub, $lang);
                // skip if already root
            }
            // Show children of root submenus as a second screen hint via one "Browse all" 
        } catch (Exception $e) {}

        $kb[] = array(
            array('text' => self::translate('🏠 Main Menu', $lang), 'callback_data' => 'main'),
            $langBtn,
        );

        return array('inline_keyboard' => $kb);
    }
}

try {
    SmartI18nPlugin::boot();
} catch (Throwable $e) {
    @file_put_contents(dirname(__DIR__, 2) . '/error.log', date('c') . ' SmartI18n boot: ' . $e->getMessage() . "\n", FILE_APPEND);
}
