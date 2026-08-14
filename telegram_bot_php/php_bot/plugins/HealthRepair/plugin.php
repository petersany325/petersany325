<?php
/**
 * HealthRepair Plugin — auto-check DB + files, repair schema/cache/bugs.
 * Survives updates: only uses hooks + ensure_schema + admin UI.
 */
require_once dirname(__DIR__) . '/loader.php';

class HealthRepairPlugin {
    public static function boot() {
        add_action('bot_boot', array(__CLASS__, 'auto_heal_light'), 5);
        // light heal on every request via webhook/admin is enough when do_action('bot_boot') is called
    }

    /** Soft checks each request (cheap) — schema at most once per minute */
    public static function auto_heal_light() {
        try {
            $stampFile = dirname(__DIR__, 2) . '/storage/schema_heal.stamp';
            $now = time();
            $last = is_file($stampFile) ? (int)@file_get_contents($stampFile) : 0;
            if (($now - $last) >= 60 && function_exists('ensure_schema')) {
                ensure_schema();
                @file_put_contents($stampFile, (string)$now);
            }
            self::ensure_branding_defaults();
        } catch (Exception $e) {
            @file_put_contents(dirname(__DIR__, 2) . '/error.log', date('c') . ' HealthRepair: ' . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    public static function ensure_branding_defaults() {
        $cfg = bot_config();
        $changed = false;
        if (!isset($cfg['bot_title']) || $cfg['bot_title'] === '') {
            // leave empty = use default in welcome_text; don't force write every time
        }
        // ensure keys exist in memory only
        if (!array_key_exists('bot_title', $cfg)) {
            $cfg['bot_title'] = 'HDD-Land Bot';
            $changed = true;
        }
        if (!array_key_exists('bot_subtitle', $cfg)) {
            $cfg['bot_subtitle'] = 'SeDiv Professional · Data Recovery';
            $changed = true;
        }
        if (!array_key_exists('welcome_text_en', $cfg)) {
            $cfg['welcome_text_en'] = '';
            $changed = true;
        }
        if (!array_key_exists('welcome_text_fa', $cfg)) {
            $cfg['welcome_text_fa'] = '';
            $changed = true;
        }
        if ($changed && function_exists('save_bot_config')) {
            // Don't auto-save from webhook (may not have save_bot_config). Skip write.
        }
    }

    public static function run_full_repair() {
        $report = array();
        $pdo = db();

        // 1) Schema
        try {
            ensure_schema();
            $report[] = array('ok', 'Database schema checked / upgraded (menus, faqs, i18n, languages, users.lang).');
        } catch (Exception $e) {
            $report[] = array('err', 'Schema repair failed: ' . $e->getMessage());
        }

        // 2) Required tables
        $tables = array('users', 'products', 'tickets', 'ticket_messages', 'faqs', 'menus', 'languages', 'menu_i18n', 'faq_i18n');
        foreach ($tables as $t) {
            try {
                $pdo->query('SELECT 1 FROM `' . str_replace('`', '', $t) . '` LIMIT 1');
                $report[] = array('ok', "Table `{$t}` OK");
            } catch (Exception $e) {
                $report[] = array('err', "Table `{$t}` missing/broken: " . $e->getMessage());
            }
        }

        // 3) Optional cache tables
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS i18n_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lang VARCHAR(10) NOT NULL,
                source_hash CHAR(32) NOT NULL,
                source_text VARCHAR(500) NOT NULL,
                translated TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_src (lang, source_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $report[] = array('ok', 'i18n_cache table ready');
        } catch (Exception $e) {
            $report[] = array('err', 'i18n_cache: ' . $e->getMessage());
        }

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS health_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                level VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $report[] = array('ok', 'health_logs table ready');
        } catch (Exception $e) {}

        // 4) Fix users.lang nulls
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'lang'")->fetch();
            if ($cols) {
                $pdo->exec("UPDATE users SET lang='en' WHERE lang IS NULL OR lang=''");
                $report[] = array('ok', 'Fixed empty user languages → en');
            }
        } catch (Exception $e) {
            $report[] = array('err', 'users.lang fix: ' . $e->getMessage());
        }

        // 5) Reseed empty critical data
        try {
            $mc = (int)$pdo->query('SELECT COUNT(*) FROM menus')->fetchColumn();
            if ($mc === 0 && function_exists('seed_default_menus')) {
                seed_default_menus($pdo);
                $report[] = array('ok', 'Menus were empty — default menu reseeded');
            } else {
                $report[] = array('ok', "Menus count: {$mc}");
            }
        } catch (Exception $e) {
            $report[] = array('err', 'Menu seed: ' . $e->getMessage());
        }

        try {
            $lc = (int)$pdo->query('SELECT COUNT(*) FROM languages')->fetchColumn();
            if ($lc === 0) {
                $ins = $pdo->prepare('INSERT INTO languages (code, name, native_name, flag, is_default, is_active, sort_order) VALUES (?,?,?,?,?,?,?)');
                $ins->execute(array('en', 'English', 'English', '🇬🇧', 1, 1, 1));
                $ins->execute(array('fa', 'Persian', 'فارسی', '🇮🇷', 0, 1, 2));
                $report[] = array('ok', 'Languages reseeded (en, fa)');
            } else {
                $report[] = array('ok', "Languages count: {$lc}");
            }
        } catch (Exception $e) {
            $report[] = array('err', 'Languages: ' . $e->getMessage());
        }

        // 6) Clear broken cache / logs
        try {
            $pdo->exec('DELETE FROM i18n_cache WHERE translated IS NULL OR translated=\'\'');
            $report[] = array('ok', 'Cleared empty i18n cache rows');
        } catch (Exception $e) {}

        // 7) File integrity
        $root = dirname(__DIR__, 2);
        $required = array(
            'bootstrap.php',
            'webhook.php',
            'menu_faq.php',
            'config.local.php',
            'admin/login.php',
            'admin/index.php',
            'admin/auth.php',
            'plugins/loader.php',
        );
        foreach ($required as $rel) {
            $path = $root . '/' . $rel;
            if (is_file($path)) {
                $report[] = array('ok', "File OK: {$rel}");
            } else {
                $report[] = array('err', "Missing file: {$rel}");
            }
        }

        // 8) Writable paths
        foreach (array($root, $root . '/admin') as $dir) {
            if (is_writable($dir)) {
                $report[] = array('ok', 'Writable: ' . basename($dir) . '/');
            } else {
                $report[] = array('err', 'Not writable: ' . $dir . ' (set 755/775)');
            }
        }
        if (is_file($root . '/config.local.php')) {
            if (is_writable($root . '/config.local.php')) {
                $report[] = array('ok', 'config.local.php is writable');
            } else {
                $report[] = array('err', 'config.local.php not writable — cannot save branding/settings');
            }
        }

        // 9) Products seed if empty
        try {
            $pc = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
            if ($pc === 0) {
                $st = $pdo->prepare('INSERT INTO products (name, description, price) VALUES (?,?,?)');
                $st->execute(array('SeDiv 2026', 'WD, Seagate, Toshiba, Samsung, Fujitsu', 840));
                $st->execute(array('SeDiv HITACHI ARM', 'Hitachi / Toshiba ARM', 640));
                $st->execute(array('SeDiv HGST', 'Disk imaging', 740));
                $report[] = array('ok', 'Products were empty — SeDiv catalog reseeded');
            } else {
                $report[] = array('ok', "Products count: {$pc}");
            }
        } catch (Exception $e) {}

        // 10) Rotate large error.log
        $log = $root . '/error.log';
        if (is_file($log) && filesize($log) > 2 * 1024 * 1024) {
            @rename($log, $log . '.' . date('Ymd_His') . '.bak');
            $report[] = array('ok', 'Rotated large error.log');
        }

        // Log summary
        try {
            $errs = 0;
            foreach ($report as $r) {
                if ($r[0] === 'err') $errs++;
            }
            $pdo->prepare('INSERT INTO health_logs (level, message) VALUES (?,?)')
                ->execute(array($errs ? 'warn' : 'ok', 'Full repair finished. Issues: ' . $errs));
        } catch (Exception $e) {}

        return $report;
    }

    public static function clear_all_caches() {
        $n = 0;
        try {
            $n = db()->exec('DELETE FROM i18n_cache');
            if ($n === false) $n = 0;
        } catch (Exception $e) {
            return array(array('err', $e->getMessage()));
        }
        $log = dirname(__DIR__, 2) . '/error.log';
        if (is_file($log)) {
            @file_put_contents($log, '');
        }
        return array(array('ok', "Cleared i18n_cache ({$n} rows) and truncated error.log"));
    }
}

try {
    HealthRepairPlugin::boot();
} catch (Throwable $e) {
    @file_put_contents(dirname(__DIR__, 2) . '/error.log', date('c') . ' HealthRepair boot: ' . $e->getMessage() . "\n", FILE_APPEND);
}
