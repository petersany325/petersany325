<?php
/**
 * One-shot probe for Menus 500 — delete after use.
 * https://hdd-land.com/telegram_bot/php_bot/admin/probe_menus.php
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Probe start\n";
try {
    require_once dirname(__DIR__) . '/bootstrap.php';
    echo "bootstrap OK\n";
} catch (Throwable $e) {
    echo "bootstrap FAIL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
    exit;
}

try {
    ensure_schema();
    echo "ensure_schema OK\n";
} catch (Throwable $e) {
    echo "ensure_schema FAIL: " . $e->getMessage() . "\n";
}

try {
    $cats = menu_categories();
    echo "menu_categories OK count=" . count($cats) . "\n";
} catch (Throwable $e) {
    echo "menu_categories FAIL: " . $e->getMessage() . "\n";
}

try {
    $langs = get_languages(false);
    echo "get_languages OK count=" . count($langs) . "\n";
} catch (Throwable $e) {
    echo "get_languages FAIL: " . $e->getMessage() . "\n";
}

try {
    $rows = db()->query('SELECT * FROM menus ORDER BY category, parent_id, row_index, sort_order, id')->fetchAll();
    echo "menus SELECT OK count=" . count($rows) . "\n";
} catch (Throwable $e) {
    echo "menus SELECT FAIL: " . $e->getMessage() . "\n";
}

echo "DONE — if all OK, upload HDDLand-Fix-HTTP500.zip and hard-refresh Menus.\n";
