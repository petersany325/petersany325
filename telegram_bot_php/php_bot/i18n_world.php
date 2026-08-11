<?php
/**
 * World languages + paginated picker + Telegram auto-detect.
 */
declare(strict_types=1);

function world_languages_seed() {
    // code, English name, native name, flag
    return array(
        array('en','English','English','🇬🇧'),
        array('fa','Persian','فارسی','🇮🇷'),
        array('ar','Arabic','العربية','🇸🇦'),
        array('zh','Chinese','中文','🇨🇳'),
        array('ru','Russian','Русский','🇷🇺'),
        array('es','Spanish','Español','🇪🇸'),
        array('fr','French','Français','🇫🇷'),
        array('de','German','Deutsch','🇩🇪'),
        array('tr','Turkish','Türkçe','🇹🇷'),
        array('pt','Portuguese','Português','🇧🇷'),
        array('hi','Hindi','हिन्दी','🇮🇳'),
        array('ur','Urdu','اردو','🇵🇰'),
        array('it','Italian','Italiano','🇮🇹'),
        array('ja','Japanese','日本語','🇯🇵'),
        array('ko','Korean','한국어','🇰🇷'),
        array('id','Indonesian','Bahasa Indonesia','🇮🇩'),
        array('ms','Malay','Bahasa Melayu','🇲🇾'),
        array('th','Thai','ไทย','🇹🇭'),
        array('vi','Vietnamese','Tiếng Việt','🇻🇳'),
        array('uk','Ukrainian','Українська','🇺🇦'),
        array('pl','Polish','Polski','🇵🇱'),
        array('nl','Dutch','Nederlands','🇳🇱'),
        array('ro','Romanian','Română','🇷🇴'),
        array('cs','Czech','Čeština','🇨🇿'),
        array('sv','Swedish','Svenska','🇸🇪'),
        array('fi','Finnish','Suomi','🇫🇮'),
        array('no','Norwegian','Norsk','🇳🇴'),
        array('da','Danish','Dansk','🇩🇰'),
        array('el','Greek','Ελληνικά','🇬🇷'),
        array('he','Hebrew','עברית','🇮🇱'),
        array('hu','Hungarian','Magyar','🇭🇺'),
        array('bg','Bulgarian','Български','🇧🇬'),
        array('sr','Serbian','Српски','🇷🇸'),
        array('hr','Croatian','Hrvatski','🇭🇷'),
        array('sk','Slovak','Slovenčina','🇸🇰'),
        array('sl','Slovenian','Slovenščina','🇸🇮'),
        array('lt','Lithuanian','Lietuvių','🇱🇹'),
        array('lv','Latvian','Latviešu','🇱🇻'),
        array('et','Estonian','Eesti','🇪🇪'),
        array('az','Azerbaijani','Azərbaycan','🇦🇿'),
        array('ka','Georgian','ქართული','🇬🇪'),
        array('hy','Armenian','Հայերեն','🇦🇲'),
        array('kk','Kazakh','Қазақша','🇰🇿'),
        array('uz','Uzbek','Oʻzbekcha','🇺🇿'),
        array('tk','Turkmen','Türkmençe','🇹🇲'),
        array('ky','Kyrgyz','Кыргызча','🇰🇬'),
        array('tg','Tajik','Тоҷикӣ','🇹🇯'),
        array('ps','Pashto','پښتو','🇦🇫'),
        array('ku','Kurdish','Kurdî','🇮🇶'),
        array('bn','Bengali','বাংলা','🇧🇩'),
        array('ta','Tamil','தமிழ்','🇮🇳'),
        array('te','Telugu','తెలుగు','🇮🇳'),
        array('mr','Marathi','मराठी','🇮🇳'),
        array('gu','Gujarati','ગુજરાતી','🇮🇳'),
        array('pa','Punjabi','ਪੰਜਾਬੀ','🇮🇳'),
        array('ml','Malayalam','മലയാളം','🇮🇳'),
        array('kn','Kannada','ಕನ್ನಡ','🇮🇳'),
        array('si','Sinhala','සිංහල','🇱🇰'),
        array('ne','Nepali','नेपाली','🇳🇵'),
        array('my','Burmese','မြန်မာ','🇲🇲'),
        array('km','Khmer','ខ្មែរ','🇰🇭'),
        array('lo','Lao','ລາວ','🇱🇦'),
        array('mn','Mongolian','Монгол','🇲🇳'),
        array('sw','Swahili','Kiswahili','🇰🇪'),
        array('am','Amharic','አማርኛ','🇪🇹'),
        array('ha','Hausa','Hausa','🇳🇬'),
        array('yo','Yoruba','Yorùbá','🇳🇬'),
        array('ig','Igbo','Igbo','🇳🇬'),
        array('zu','Zulu','isiZulu','🇿🇦'),
        array('af','Afrikaans','Afrikaans','🇿🇦'),
        array('sq','Albanian','Shqip','🇦🇱'),
        array('bs','Bosnian','Bosanski','🇧🇦'),
        array('mk','Macedonian','Македонски','🇲🇰'),
        array('is','Icelandic','Íslenska','🇮🇸'),
        array('ga','Irish','Gaeilge','🇮🇪'),
        array('cy','Welsh','Cymraeg','🇬🇧'),
        array('mt','Maltese','Malti','🇲🇹'),
        array('eu','Basque','Euskara','🇪🇸'),
        array('ca','Catalan','Català','🇪🇸'),
        array('gl','Galician','Galego','🇪🇸'),
        array('be','Belarusian','Беларуская','🇧🇾'),
        array('tl','Filipino','Filipino','🇵🇭'),
        array('jv','Javanese','Basa Jawa','🇮🇩'),
        array('su','Sundanese','Basa Sunda','🇮🇩'),
        array('ceb','Cebuano','Cebuano','🇵🇭'),
        array('haw','Hawaiian','ʻŌlelo Hawaiʻi','🇺🇸'),
        array('sm','Samoan','Gagana Samoa','🇼🇸'),
        array('mg','Malagasy','Malagasy','🇲🇬'),
        array('so','Somali','Soomaali','🇸🇴'),
        array('rw','Kinyarwanda','Ikinyarwanda','🇷🇼'),
        array('ny','Chichewa','Chichewa','🇲🇼'),
        array('xh','Xhosa','isiXhosa','🇿🇦'),
        array('st','Sesotho','Sesotho','🇱🇸'),
        array('sn','Shona','chiShona','🇿🇼'),
        array('eo','Esperanto','Esperanto','🌍'),
        array('la','Latin','Latina','🇻🇦'),
    );
}

function ensure_world_languages($pdo = null) {
    $pdo = $pdo ? $pdo : db();
    try {
        // Avoid emoji crashes on non-utf8mb4 MySQL tables
        @$pdo->exec('ALTER TABLE languages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (Throwable $e) {}

    try {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM languages')->fetchColumn();
    } catch (Throwable $e) {
        return;
    }
    $seed = world_languages_seed();
    if ($count < 40) {
        try {
            $ins = $pdo->prepare('INSERT IGNORE INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?,?,?,?,?,?,?)');
            $i = 1;
            foreach ($seed as $row) {
                $isDefault = ($row[0] === 'en') ? 1 : 0;
                $flag = isset($row[3]) ? (string)$row[3] : '';
                try {
                    $ins->execute(array($row[0], $row[1], $row[2], $flag, $isDefault, 1, $i));
                } catch (Throwable $e) {
                    // Retry without emoji flag (common on utf8 / latin1 DBs)
                    try {
                        $ins->execute(array($row[0], $row[1], $row[2], '', $isDefault, 1, $i));
                    } catch (Throwable $e2) {}
                }
                $i++;
            }
        } catch (Throwable $e) {}
    }
}

function detect_telegram_lang($from) {
    $code = isset($from['language_code']) ? strtolower(substr($from['language_code'], 0, 2)) : '';
    if ($code === '') {
        return 'en';
    }
    // map variants
    if ($code === 'nb' || $code === 'nn') $code = 'no';
    if ($code === 'iw') $code = 'he';
    if ($code === 'in') $code = 'id';
    if ($code === 'ji') $code = 'yi';
    try {
        $st = db()->prepare('SELECT code FROM languages WHERE code=? AND is_active=1');
        $st->execute(array($code));
        if ($st->fetchColumn()) {
            return $code;
        }
    } catch (Exception $e) {}
    return 'en';
}

/**
 * Graphical language keyboard.
 * $page: 0 = featured + detected, 1+ = more pages
 */
function lang_keyboard_world($fromStart = true, $page = 0, $detected = 'en') {
    ensure_world_languages();
    $prefix = $fromStart ? 'startlang:' : 'setlang:';
    $all = get_languages(true);

    // Featured first page
    $featuredCodes = array('en','fa','ar','zh','ru','es','fr','de','tr','pt','hi','ur');
    if ($detected && !in_array($detected, $featuredCodes, true)) {
        array_unshift($featuredCodes, $detected);
    } elseif ($detected) {
        // move detected to top
        $featuredCodes = array_values(array_unique(array_merge(array($detected), $featuredCodes)));
    }

    $byCode = array();
    foreach ($all as $l) {
        $byCode[$l['code']] = $l;
    }

    $kb = array();
    if ($page <= 0) {
        $row = array();
        foreach ($featuredCodes as $code) {
            if (!isset($byCode[$code])) continue;
            $l = $byCode[$code];
            $label = trim($l['flag'] . ' ' . $l['native_name']);
            if ($code === $detected) {
                $label = '⭐ ' . $label;
            }
            $row[] = array('text' => $label, 'callback_data' => $prefix . $code);
            if (count($row) === 2) {
                $kb[] = $row;
                $row = array();
            }
        }
        if ($row) $kb[] = $row;
        $kb[] = array(array('text' => '🌍 More languages · زبان‌های بیشتر', 'callback_data' => 'langpage:1'));
    } else {
        // Paginate rest alphabetically by English name, 8 per page
        $rest = array();
        foreach ($all as $l) {
            if (!in_array($l['code'], array('en','fa'), true)) {
                $rest[] = $l;
            }
        }
        usort($rest, function ($a, $b) { return strcmp($a['name'], $b['name']); });
        $per = 8;
        $offset = ($page - 1) * $per;
        $slice = array_slice($rest, $offset, $per);
        $row = array();
        foreach ($slice as $l) {
            $row[] = array('text' => trim($l['flag'] . ' ' . $l['native_name']), 'callback_data' => $prefix . $l['code']);
            if (count($row) === 2) {
                $kb[] = $row;
                $row = array();
            }
        }
        if ($row) $kb[] = $row;
        $nav = array();
        if ($page > 1) {
            $nav[] = array('text' => '⬅️ Prev', 'callback_data' => 'langpage:' . ($page - 1));
        }
        if ($offset + $per < count($rest)) {
            $nav[] = array('text' => 'Next ➡️', 'callback_data' => 'langpage:' . ($page + 1));
        }
        if ($nav) $kb[] = $nav;
        $kb[] = array(array('text' => '⭐ Popular languages', 'callback_data' => 'langpage:0'));
    }
    return array('inline_keyboard' => $kb);
}

/** Professional graphical main hub after language */
function graphical_main_hub($lang = 'en') {
    $items = function_exists('get_menu_items') ? get_menu_items(null, $lang) : array();
    $kb = array();
    $on = function ($f) {
        return !function_exists('feature_on') || feature_on($f);
    };

    // Always show professional top actions (feature-aware)
    if ($lang === 'fa') {
        $top = array();
        if ($on('shop') || $on('prodesk')) {
            $top[] = array(
                array('text' => '🛒 فروشگاه SeDiv', 'callback_data' => 'shop'),
                array('text' => '💼 میز حرفه‌ای', 'callback_data' => 'reqhub'),
            );
        }
        if ($on('prodesk')) {
            $top[] = array(
                array('text' => '🛠️ پشتیبانی', 'callback_data' => 'req:support'),
                array('text' => '💎 فروش نرم‌افزار', 'callback_data' => 'req:sales'),
            );
        }
        if ($on('tickets') || $on('profile')) {
            $tk = array();
            if ($on('tickets')) {
                $tk[] = array('text' => '🎫 تیکت‌های من', 'callback_data' => 'mytickets');
            }
            if ($on('profile')) {
                $tk[] = array('text' => '👤 پروفایل', 'callback_data' => 'profile');
            }
            $top[] = $tk;
        }
        $top[] = array(
            array('text' => '❓ سوالات', 'callback_data' => 'faqcat:all'),
            array('text' => '📋 انجمن', 'callback_data' => 'forum'),
        );
        $top[] = array(
            array('text' => '🎓 آموزش', 'callback_data' => 'cmd:training'),
            array('text' => '🌐 وب‌سایت', 'url' => bot_config()['site_url']),
        );
        $acc = array();
        if ($on('license')) {
            $acc[] = array('text' => '🔑 لایسنس', 'callback_data' => 'license');
        }
        if ($on('orders')) {
            $acc[] = array('text' => '📦 سفارش‌ها', 'callback_data' => 'orders');
        }
        if ($acc) {
            $top[] = $acc;
        }
        $more = array();
        if ($on('cart')) {
            $more[] = array('text' => '🛒 سبد', 'callback_data' => 'cart');
        }
        if ($on('payments')) {
            $more[] = array('text' => '💳 پرداخت', 'callback_data' => 'checkout');
        }
        if ($more) {
            $top[] = $more;
        }
        $eng = array();
        if ($on('contact')) {
            $eng[] = array('text' => '☎️ تماس', 'callback_data' => 'contact');
        }
        if ($on('referral')) {
            $eng[] = array('text' => '🎁 معرفی', 'callback_data' => 'referral');
        }
        if ($eng) {
            $top[] = $eng;
        }
        if ($on('vip_download')) {
            $top[] = array(array('text' => '💎 دانلود VIP', 'callback_data' => 'vipdl'));
        }
        $top[] = array(
            array('text' => '🌍 زبان', 'callback_data' => 'lang'),
            array('text' => 'ℹ️ راهنما', 'callback_data' => 'help'),
        );
    } else {
        $top = array();
        if ($on('shop') || $on('prodesk')) {
            $top[] = array(
                array('text' => '🛒 SeDiv Shop', 'callback_data' => 'shop'),
                array('text' => '💼 Pro Desk', 'callback_data' => 'reqhub'),
            );
        }
        if ($on('prodesk')) {
            $top[] = array(
                array('text' => '🛠️ Support', 'callback_data' => 'req:support'),
                array('text' => '💎 Software Sales', 'callback_data' => 'req:sales'),
            );
        }
        if ($on('tickets') || $on('profile')) {
            $tk = array();
            if ($on('tickets')) {
                $tk[] = array('text' => '🎫 My Tickets', 'callback_data' => 'mytickets');
            }
            if ($on('profile')) {
                $tk[] = array('text' => '👤 Profile', 'callback_data' => 'profile');
            }
            $top[] = $tk;
        }
        $top[] = array(
            array('text' => '❓ FAQ', 'callback_data' => 'faqcat:all'),
            array('text' => '📋 Forum', 'callback_data' => 'forum'),
        );
        $top[] = array(
            array('text' => '🎓 Training', 'callback_data' => 'cmd:training'),
            array('text' => '🌐 Website', 'url' => bot_config()['site_url']),
        );
        $acc = array();
        if ($on('license')) {
            $acc[] = array('text' => '🔑 License', 'callback_data' => 'license');
        }
        if ($on('orders')) {
            $acc[] = array('text' => '📦 Orders', 'callback_data' => 'orders');
        }
        if ($acc) {
            $top[] = $acc;
        }
        $more = array();
        if ($on('cart')) {
            $more[] = array('text' => '🛒 Cart', 'callback_data' => 'cart');
        }
        if ($on('payments')) {
            $more[] = array('text' => '💳 Checkout', 'callback_data' => 'checkout');
        }
        if ($more) {
            $top[] = $more;
        }
        $eng = array();
        if ($on('contact')) {
            $eng[] = array('text' => '☎️ Contact', 'callback_data' => 'contact');
        }
        if ($on('referral')) {
            $eng[] = array('text' => '🎁 Referral', 'callback_data' => 'referral');
        }
        if ($eng) {
            $top[] = $eng;
        }
        if ($on('vip_download')) {
            $top[] = array(array('text' => '💎 VIP Download', 'callback_data' => 'vipdl'));
        }
        $top[] = array(
            array('text' => '🌍 Language', 'callback_data' => 'lang'),
            array('text' => 'ℹ️ Help', 'callback_data' => 'help'),
        );
    }
    $kb = $top;

    // Append custom root menu items (not duplicates of hub buttons)
    $used = array('shop','reqhub','req:support','req:sales','faqcat:all','forum','cmd:training','lang','help','main','cart','orders','checkout','license','renew','demo','profile','feedback','referral','contact','brands','news','miniapp','mytickets','support_cancel','support','vipdl');
    $row = array();
    foreach ($items as $it) {
        $titleLow = function_exists('mb_strtolower') ? mb_strtolower((string)$it['title'], 'UTF-8') : strtolower((string)$it['title']);
        // Hub already has Website + Pro Desk — skip duplicates from DB roots
        if ($it['menu_type'] === 'url') {
            continue;
        }
        if ($it['menu_type'] === 'submenu' && (strpos($titleLow, 'pro desk') !== false || strpos($titleLow, 'میز') !== false)) {
            continue;
        }
        if ($it['menu_type'] === 'callback' && in_array((string)$it['value_text'], array('support', 'reqhub'), true)) {
            continue;
        }
        $cb = '';
        if ($it['menu_type'] === 'callback') $cb = $it['value_text'];
        elseif ($it['menu_type'] === 'faq_list') $cb = 'faqcat:all';
        elseif ($it['menu_type'] === 'command') $cb = 'cmd:' . $it['value_text'];
        elseif ($it['menu_type'] === 'submenu') $cb = 'menu:' . $it['id'];
        if ($cb && in_array($cb, $used, true)) continue;
        $btn = array('text' => $it['title'], 'callback_data' => $cb ?: ('menutxt:' . $it['id']));
        $row[] = $btn;
        $used[] = $cb;
        if (count($row) === 2) {
            $kb[] = $row;
            $row = array();
        }
    }
    if ($row) $kb[] = $row;

    return array('inline_keyboard' => $kb);
}
