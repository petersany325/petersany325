<?php
declare(strict_types=1);

/**
 * Schema + menus/FAQ with categories, nested submenus, multi-language.
 */
function ensure_schema($pdo = null) {
    $pdo = $pdo ? $pdo : db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS languages (
        code VARCHAR(10) PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        native_name VARCHAR(80) NOT NULL,
        flag VARCHAR(16) DEFAULT '',
        is_default TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(120) DEFAULT 'General',
        keywords VARCHAR(500) NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS menus (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT NULL DEFAULT NULL,
        category VARCHAR(80) DEFAULT 'Main',
        title VARCHAR(120) NOT NULL,
        menu_type VARCHAR(40) NOT NULL DEFAULT 'text',
        value_text TEXT NULL,
        row_index INT DEFAULT 0,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (parent_id),
        INDEX (category),
        INDEX (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_i18n (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_id INT NOT NULL,
        lang VARCHAR(10) NOT NULL,
        title VARCHAR(120) NOT NULL,
        value_text TEXT NULL,
        UNIQUE KEY uniq_menu_lang (menu_id, lang),
        INDEX (lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS faq_i18n (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faq_id INT NOT NULL,
        lang VARCHAR(10) NOT NULL,
        question VARCHAR(500) NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(120) NULL,
        UNIQUE KEY uniq_faq_lang (faq_id, lang),
        INDEX (lang)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Upgrade: add category column if missing
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM menus LIKE 'category'")->fetch();
        if (!$cols) {
            $pdo->exec("ALTER TABLE menus ADD COLUMN category VARCHAR(80) DEFAULT 'Main' AFTER parent_id");
        }
    } catch (Exception $e) {}

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'lang'")->fetch();
        if (!$cols) {
            $pdo->exec("ALTER TABLE users ADD COLUMN lang VARCHAR(10) DEFAULT 'en'");
        }
    } catch (Exception $e) {}

    // Seed languages
    $lc = (int)$pdo->query('SELECT COUNT(*) FROM languages')->fetchColumn();
    if ($lc === 0) {
        $ins = $pdo->prepare('INSERT INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?,?,?,?,?,?,?)');
        $seedLangs = array(
            array('en', 'English', 'English', '🇬🇧', 1, 1, 1),
            array('fa', 'Persian', 'فارسی', '🇮🇷', 0, 1, 2),
            array('ru', 'Russian', 'Русский', '🇷🇺', 0, 1, 3),
            array('zh', 'Chinese', '中文', '🇨🇳', 0, 1, 4),
        );
        foreach ($seedLangs as $row) {
            try {
                $ins->execute($row);
            } catch (Throwable $e) {
                $row[3] = '';
                try { $ins->execute($row); } catch (Throwable $e2) {}
            }
        }
    }

    // Seed FAQs + i18n
    $faqCount = (int)$pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
    if ($faqCount === 0) {
        $ins = $pdo->prepare('INSERT INTO faqs (question, answer, category, keywords, sort_order) VALUES (?,?,?,?,?)');
        $seedFaqs = array(
            array('What is SeDiv?', 'SeDiv is professional HDD firmware repair software for data recovery labs. Learn more at https://hdd-land.com', 'General', 'sediv,software', 1),
            array('Which drives are supported?', "SeDiv 2026 supports WD, Seagate, Toshiba, Samsung and Fujitsu.\nSeDiv HITACHI ARM supports Hitachi / Toshiba ARM.\nSeDiv HGST is for high-performance imaging.", 'Products', 'wd,seagate,toshiba,support', 2),
            array('How can I buy a license?', 'Visit https://hdd-land.com or use /shop in this bot to browse editions and open the purchase page.', 'Sales', 'buy,license,price', 3),
            array('How do I get technical support?', 'Send /ticket your issue here, or post on the forum: https://hdd-land.com/forum', 'Support', 'support,help,ticket', 4),
            array('Where is training?', 'SeDiv professional training is available via HDD-Land. Use /training or visit the forum training sections.', 'Training', 'training,course', 5),
        );
        foreach ($seedFaqs as $f) {
            $ins->execute($f);
        }
        // Persian translations
        $map = $pdo->query('SELECT id, question FROM faqs ORDER BY id')->fetchAll();
        $ti = $pdo->prepare('INSERT INTO faq_i18n (faq_id, lang, question, answer, category) VALUES (?,?,?,?,?)');
        $fa = array(
            array('SeDiv چیست؟', 'SeDiv نرم‌افزار حرفه‌ای تعمیر فریمور هارد برای آزمایشگاه‌های ریکاوری است. بیشتر: https://hdd-land.com', 'عمومی'),
            array('کدام هاردها پشتیبانی می‌شوند؟', "SeDiv 2026 از WD، Seagate، Toshiba، Samsung و Fujitsu پشتیبانی می‌کند.\nنسخه HITACHI ARM و HGST نیز موجود است.", 'محصولات'),
            array('چطور لایسنس بخرم؟', 'به https://hdd-land.com مراجعه کنید یا از دستور /shop در ربات استفاده کنید.', 'فروش'),
            array('پشتیبانی فنی چطور؟', 'دستور /ticket را بفرستید یا در فروم بنویسید: https://hdd-land.com/forum', 'پشتیبانی'),
            array('آموزش کجاست؟', 'آموزش حرفه‌ای SeDiv از طریق HDD-Land در دسترس است. /training را بزنید.', 'آموزش'),
        );
        foreach ($map as $i => $row) {
            if (isset($fa[$i])) {
                $ti->execute(array($row['id'], 'fa', $fa[$i][0], $fa[$i][1], $fa[$i][2]));
            }
        }
    }

    // Seed menus with categories + nested submenu + i18n
    $menuCount = (int)$pdo->query('SELECT COUNT(*) FROM menus')->fetchColumn();
    if ($menuCount === 0) {
        seed_default_menus($pdo);
    } else {
        // backfill empty categories
        $pdo->exec("UPDATE menus SET category='Main' WHERE category IS NULL OR category=''");
    }

    try {
        if (function_exists('ensure_requests_schema')) {
            ensure_requests_schema($pdo);
        }
    } catch (Throwable $e) {}
    try {
        if (function_exists('ensure_world_languages')) {
            ensure_world_languages($pdo);
        }
    } catch (Throwable $e) {}
    try {
        if (function_exists('ensure_admins_schema')) {
            ensure_admins_schema($pdo);
        }
    } catch (Throwable $e) {}
}

function seed_default_menus($pdo) {
    $ins = $pdo->prepare('INSERT INTO menus (parent_id, category, title, menu_type, value_text, row_index, sort_order) VALUES (?,?,?,?,?,?,?)');
    // Commerce
    $ins->execute(array(null, 'Commerce', '🛒 Shop', 'callback', 'shop', 0, 1));
    $ins->execute(array(null, 'Community', '📋 Forum', 'callback', 'forum', 0, 2));
    // Support
    $ins->execute(array(null, 'Support', '❓ FAQ', 'faq_list', '', 1, 1));
    $ins->execute(array(null, 'Support', '🎫 Support', 'callback', 'support', 1, 2));
    // Resources submenu
    $ins->execute(array(null, 'Resources', '📚 Resources', 'submenu', '', 2, 1));
    $ins->execute(array(null, 'System', '🌐 Website', 'url', 'https://hdd-land.com', 2, 2));
    $ins->execute(array(null, 'System', '🌍 Language', 'callback', 'lang', 3, 1));

    $st = $pdo->query("SELECT id FROM menus WHERE title='📚 Resources' AND parent_id IS NULL LIMIT 1");
    $resourcesId = (int)$st->fetchColumn();
    if ($resourcesId > 0) {
        $ins->execute(array($resourcesId, 'Resources', '🎓 Training', 'command', 'training', 0, 1));
        $ins->execute(array($resourcesId, 'Resources', 'ℹ️ Help', 'callback', 'help', 0, 2));
        // nested submenu under Resources
        $ins->execute(array($resourcesId, 'Resources', '🔧 Brands', 'submenu', '', 1, 1));
        $st2 = $pdo->query("SELECT id FROM menus WHERE title='🔧 Brands' LIMIT 1");
        $brandsId = (int)$st2->fetchColumn();
        if ($brandsId > 0) {
            $ins->execute(array($brandsId, 'Resources', 'WD', 'text', 'Western Digital resources & firmware tips: https://hdd-land.com/forum', 0, 1));
            $ins->execute(array($brandsId, 'Resources', 'Seagate', 'text', 'Seagate F3 resources: https://hdd-land.com/forum', 0, 2));
            $ins->execute(array($brandsId, 'Resources', 'Toshiba', 'text', 'Toshiba resources: https://hdd-land.com/forum', 1, 1));
        }
        $ins->execute(array($resourcesId, 'System', '🏠 Main Menu', 'callback', 'main', 2, 1));
    }

    // FA translations for root titles
    $ti = $pdo->prepare('INSERT INTO menu_i18n (menu_id, lang, title, value_text) VALUES (?,?,?,?)');
    $rows = $pdo->query('SELECT id, title, value_text FROM menus')->fetchAll();
    $faTitles = array(
        '🛒 Shop' => '🛒 فروشگاه',
        '📋 Forum' => '📋 انجمن',
        '❓ FAQ' => '❓ سوالات متداول',
        '🎫 Support' => '🎫 پشتیبانی',
        '📚 Resources' => '📚 منابع',
        '🌐 Website' => '🌐 وب‌سایت',
        '🌍 Language' => '🌍 زبان',
        '🎓 Training' => '🎓 آموزش',
        'ℹ️ Help' => 'ℹ️ راهنما',
        '🔧 Brands' => '🔧 برندها',
        '🏠 Main Menu' => '🏠 منوی اصلی',
        'WD' => 'وسترن دیجیتال',
        'Seagate' => 'سیگیت',
        'Toshiba' => 'توشیبا',
    );
    foreach ($rows as $r) {
        if (isset($faTitles[$r['title']])) {
            $ti->execute(array($r['id'], 'fa', $faTitles[$r['title']], $r['value_text']));
        }
    }
}

function menu_categories() {
    return array('Main', 'Commerce', 'Community', 'Support', 'Resources', 'System');
}

function get_languages($activeOnly = true) {
    ensure_schema();
    $sql = 'SELECT * FROM languages';
    if ($activeOnly) {
        $sql .= ' WHERE is_active=1';
    }
    $sql .= ' ORDER BY sort_order ASC, code ASC';
    return db()->query($sql)->fetchAll();
}

function default_lang() {
    ensure_schema();
    $code = db()->query('SELECT code FROM languages WHERE is_default=1 AND is_active=1 LIMIT 1')->fetchColumn();
    return $code ? $code : 'en';
}

function user_lang($userId) {
    ensure_schema();
    if (!$userId) {
        return default_lang();
    }
    $st = db()->prepare('SELECT lang FROM users WHERE telegram_id=?');
    $st->execute(array((int)$userId));
    $lang = $st->fetchColumn();
    if ($lang) {
        return $lang;
    }
    return default_lang();
}

function set_user_lang($userId, $lang) {
    ensure_schema();
    $st = db()->prepare('UPDATE users SET lang=? WHERE telegram_id=?');
    $st->execute(array($lang, (int)$userId));
}

function localize_menu_row($row, $lang) {
    if (!$lang || $lang === 'en') {
        // still check i18n override for en if exists
    }
    $st = db()->prepare('SELECT title, value_text FROM menu_i18n WHERE menu_id=? AND lang=?');
    $st->execute(array($row['id'], $lang));
    $tr = $st->fetch();
    if ($tr) {
        $row['title'] = $tr['title'];
        if ($tr['value_text'] !== null && $tr['value_text'] !== '') {
            $row['value_text'] = $tr['value_text'];
        }
    }
    if (function_exists('apply_filters')) {
        $row = apply_filters('localize_menu_row', $row, $lang);
    }
    return $row;
}

function localize_faq_row($row, $lang) {
    $st = db()->prepare('SELECT question, answer, category FROM faq_i18n WHERE faq_id=? AND lang=?');
    $st->execute(array($row['id'], $lang));
    $tr = $st->fetch();
    if ($tr) {
        $row['question'] = $tr['question'];
        $row['answer'] = $tr['answer'];
        if (!empty($tr['category'])) {
            $row['category'] = $tr['category'];
        }
    }
    if (function_exists('apply_filters')) {
        $row = apply_filters('localize_faq_row', $row, $lang);
    }
    return $row;
}

function get_menu_items($parentId = null, $lang = null) {
    ensure_schema();
    $lang = $lang ? $lang : default_lang();
    $pdo = db();
    if ($parentId === null) {
        $stmt = $pdo->query('SELECT * FROM menus WHERE parent_id IS NULL AND is_active=1 ORDER BY row_index ASC, sort_order ASC, id ASC');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE parent_id = ? AND is_active=1 ORDER BY row_index ASC, sort_order ASC, id ASC');
        $stmt->execute(array($parentId));
    }
    $items = $stmt->fetchAll();
    $out = array();
    foreach ($items as $item) {
        $out[] = localize_menu_row($item, $lang);
    }
    return $out;
}

function build_menu_keyboard($parentId = null, $lang = null) {
    $lang = $lang ? $lang : default_lang();
    $items = get_menu_items($parentId, $lang);
    $rows = array();
    foreach ($items as $item) {
        $r = (int)$item['row_index'];
        if (!isset($rows[$r])) {
            $rows[$r] = array();
        }
        $btn = array('text' => $item['title']);
        $type = $item['menu_type'];
        if ($type === 'url') {
            $btn['url'] = $item['value_text'] ? $item['value_text'] : 'https://hdd-land.com';
        } elseif ($type === 'submenu') {
            $btn['callback_data'] = 'menu:' . $item['id'];
        } elseif ($type === 'faq_list') {
            $btn['callback_data'] = 'faqcat:all';
        } elseif ($type === 'callback') {
            $btn['callback_data'] = $item['value_text'] ? $item['value_text'] : 'main';
        } elseif ($type === 'command') {
            $btn['callback_data'] = 'cmd:' . ($item['value_text'] ? $item['value_text'] : 'help');
        } else {
            $btn['callback_data'] = 'menutxt:' . $item['id'];
        }
        $rows[$r][] = $btn;
    }
    ksort($rows);
    $keyboard = array_values($rows);
    if ($parentId !== null) {
        // Back goes to parent of current, or root
        $st = db()->prepare('SELECT parent_id FROM menus WHERE id=?');
        $st->execute(array($parentId));
        $pp = $st->fetchColumn();
        $back = ($pp === null || $pp === false || $pp === '') ? 'menu:root' : ('menu:' . (int)$pp);
        $backLabel = ($lang === 'fa') ? '⬅️ بازگشت' : '⬅️ Back';
        $keyboard[] = array(array('text' => $backLabel, 'callback_data' => $back));
    }
    $result = array('inline_keyboard' => $keyboard);
    if (function_exists('apply_filters')) {
        $result = apply_filters('build_menu_keyboard', $result, $parentId, $lang);
    }
    return $result;
}

function lang_keyboard($fromStart = false) {
    $langs = get_languages(true);
    // Prefer English first for HDD-Land EN site
    usort($langs, function ($a, $b) {
        if ($a['code'] === 'en') return -1;
        if ($b['code'] === 'en') return 1;
        return ((int)$a['sort_order']) - ((int)$b['sort_order']);
    });
    $kb = array();
    $row = array();
    $prefix = $fromStart ? 'startlang:' : 'setlang:';
    foreach ($langs as $l) {
        $label = trim($l['flag'] . ' ' . $l['native_name']);
        if ($l['code'] === 'en') {
            $label = '🇬🇧 English';
        } elseif ($l['code'] === 'fa') {
            $label = '🇮🇷 فارسی';
        }
        $row[] = array(
            'text' => $label,
            'callback_data' => $prefix . $l['code'],
        );
        if (count($row) === 2) {
            $kb[] = $row;
            $row = array();
        }
    }
    if ($row) {
        $kb[] = $row;
    }
    return array('inline_keyboard' => $kb);
}

function welcome_text($lang) {
    $cfg = bot_config();
    $title = !empty($cfg['bot_title']) ? $cfg['bot_title'] : 'HDD-Land Bot';
    $sub = !empty($cfg['bot_subtitle']) ? $cfg['bot_subtitle'] : 'SeDiv Professional · Data Recovery';

    if ($lang === 'fa' && !empty($cfg['welcome_text_fa'])) {
        return $cfg['welcome_text_fa'];
    }
    if ($lang !== 'fa' && !empty($cfg['welcome_text_en'])) {
        return $cfg['welcome_text_en'];
    }

    if ($lang === 'fa') {
        return "🏠 <b>به ربات " . htmlspecialchars($title) . " خوش آمدید!</b>\n\n"
            . htmlspecialchars($sub) . "\n\n"
            . "🌐 " . $cfg['site_url'] . "\n"
            . "📋 " . $cfg['forum_url'] . "\n\n"
            . "از منوی زیر یک گزینه را انتخاب کنید.";
    }
    return "🏠 <b>Welcome to " . htmlspecialchars($title) . "</b>\n\n"
        . htmlspecialchars($sub) . "\n\n"
        . "🌐 Website: " . $cfg['site_url'] . "\n"
        . "📋 Forum: " . $cfg['forum_url'] . "\n\n"
        . "Choose an option from the menu below.";
}

function language_gate_text() {
    $cfg = bot_config();
    if (!empty($cfg['gate_text'])) {
        $text = $cfg['gate_text'];
    } else {
        $title = !empty($cfg['bot_title']) ? $cfg['bot_title'] : 'HDD-Land Bot';
        $sub = !empty($cfg['bot_subtitle']) ? $cfg['bot_subtitle'] : 'SeDiv Professional · Data Recovery';
        $text = "🌍 <b>" . htmlspecialchars($title) . "</b>\n"
            . htmlspecialchars($sub) . "\n\n"
            . "<b>Please select your language</b>\n"
            . "لطفاً زبان خود را انتخاب کنید\n\n"
            . "🇬🇧 English  ·  🇮🇷 فارسی";
    }
    if (function_exists('apply_filters')) {
        $text = apply_filters('language_gate_text', $text);
    }
    return $text;
}

function get_active_faqs($category = null, $lang = null) {
    ensure_schema();
    $lang = $lang ? $lang : default_lang();
    $pdo = db();
    if ($category && $category !== 'all') {
        // match either base or translated category — load all then filter localized
        $rows = $pdo->query('SELECT * FROM faqs WHERE is_active=1 ORDER BY sort_order ASC, id ASC')->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $row = localize_faq_row($row, $lang);
            if ($row['category'] === $category) {
                $out[] = $row;
            }
        }
        return $out;
    }
    $rows = $pdo->query('SELECT * FROM faqs WHERE is_active=1 ORDER BY category ASC, sort_order ASC, id ASC')->fetchAll();
    $out = array();
    foreach ($rows as $row) {
        $out[] = localize_faq_row($row, $lang);
    }
    return $out;
}

function get_faq_categories($lang = null) {
    $faqs = get_active_faqs(null, $lang);
    $cats = array();
    foreach ($faqs as $f) {
        $cats[$f['category']] = true;
    }
    return array_keys($cats);
}

function faq_keyboard($category = null, $lang = null) {
    $lang = $lang ? $lang : default_lang();
    $faqs = get_active_faqs($category, $lang);
    $kb = array();
    foreach ($faqs as $f) {
        $kb[] = array(array('text' => $f['question'], 'callback_data' => 'faq:' . $f['id']));
    }
    $cats = get_faq_categories($lang);
    if (count($cats) > 1 && ($category === null || $category === 'all')) {
        $row = array();
        foreach ($cats as $c) {
            $row[] = array('text' => $c, 'callback_data' => 'faqcat:' . rawurlencode($c));
            if (count($row) === 3) {
                array_unshift($kb, $row);
                $row = array();
            }
        }
        if ($row) {
            array_unshift($kb, $row);
        }
    }
    $main = ($lang === 'fa') ? '⬅️ منوی اصلی' : '⬅️ Main Menu';
    $kb[] = array(array('text' => $main, 'callback_data' => 'main'));
    return array('inline_keyboard' => $kb);
}

function search_faqs($q, $lang = null) {
    ensure_schema();
    $lang = $lang ? $lang : default_lang();
    $like = '%' . $q . '%';
    // Search base + translations
    $sql = "SELECT DISTINCT f.* FROM faqs f
        LEFT JOIN faq_i18n i ON i.faq_id=f.id
        WHERE f.is_active=1 AND (
            f.question LIKE ? OR f.answer LIKE ? OR f.keywords LIKE ?
            OR i.question LIKE ? OR i.answer LIKE ?
        )
        ORDER BY f.sort_order ASC LIMIT 10";
    $stmt = db()->prepare($sql);
    $stmt->execute(array($like, $like, $like, $like, $like));
    $rows = $stmt->fetchAll();
    $out = array();
    foreach ($rows as $row) {
        $out[] = localize_faq_row($row, $lang);
    }
    return $out;
}

function save_menu_translation($menuId, $lang, $title, $valueText = null) {
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM menu_i18n WHERE menu_id=? AND lang=?');
    $st->execute(array($menuId, $lang));
    if ($st->fetch()) {
        $pdo->prepare('UPDATE menu_i18n SET title=?, value_text=? WHERE menu_id=? AND lang=?')
            ->execute(array($title, $valueText, $menuId, $lang));
    } else {
        $pdo->prepare('INSERT INTO menu_i18n (menu_id, lang, title, value_text) VALUES (?,?,?,?)')
            ->execute(array($menuId, $lang, $title, $valueText));
    }
}

function save_faq_translation($faqId, $lang, $question, $answer, $category = null) {
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM faq_i18n WHERE faq_id=? AND lang=?');
    $st->execute(array($faqId, $lang));
    if ($st->fetch()) {
        $pdo->prepare('UPDATE faq_i18n SET question=?, answer=?, category=? WHERE faq_id=? AND lang=?')
            ->execute(array($question, $answer, $category, $faqId, $lang));
    } else {
        $pdo->prepare('INSERT INTO faq_i18n (faq_id, lang, question, answer, category) VALUES (?,?,?,?,?)')
            ->execute(array($faqId, $lang, $question, $answer, $category));
    }
}

function get_menu_translation($menuId, $lang) {
    $st = db()->prepare('SELECT * FROM menu_i18n WHERE menu_id=? AND lang=?');
    $st->execute(array($menuId, $lang));
    return $st->fetch() ?: null;
}

function get_faq_translation($faqId, $lang) {
    $st = db()->prepare('SELECT * FROM faq_i18n WHERE faq_id=? AND lang=?');
    $st->execute(array($faqId, $lang));
    return $st->fetch() ?: null;
}

function build_menu_tree($items = null) {
    if ($items === null) {
        $items = db()->query('SELECT * FROM menus ORDER BY category ASC, COALESCE(parent_id,0) ASC, row_index ASC, sort_order ASC')->fetchAll();
    }
    $byParent = array();
    foreach ($items as $it) {
        $pid = $it['parent_id'] === null ? 0 : (int)$it['parent_id'];
        if (!isset($byParent[$pid])) {
            $byParent[$pid] = array();
        }
        $byParent[$pid][] = $it;
    }
    $flat = array();
    $walk = function ($parent, $depth) use (&$walk, &$flat, $byParent) {
        if (!isset($byParent[$parent])) {
            return;
        }
        foreach ($byParent[$parent] as $node) {
            $node['_depth'] = $depth;
            $flat[] = $node;
            $walk((int)$node['id'], $depth + 1);
        }
    };
    $walk(0, 0);
    return $flat;
}
