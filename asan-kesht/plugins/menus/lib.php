<?php

declare(strict_types=1);

function menu_install(Database $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ak_menu_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parent_id INT UNSIGNED NULL,
            label VARCHAR(80) NOT NULL,
            href VARCHAR(240) NOT NULL DEFAULT "",
            description VARCHAR(180) NOT NULL DEFAULT "",
            sort_order INT NOT NULL DEFAULT 0,
            visible TINYINT(1) NOT NULL DEFAULT 1,
            new_tab TINYINT(1) NOT NULL DEFAULT 0,
            mega TINYINT(1) NOT NULL DEFAULT 0,
            icon VARCHAR(20) NOT NULL DEFAULT "",
            badge VARCHAR(24) NOT NULL DEFAULT "",
            featured TINYINT(1) NOT NULL DEFAULT 0,
            INDEX parent_sort (parent_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $count = $db->one('SELECT COUNT(*) AS c FROM ak_menu_items');
    if ((int) ($count['c'] ?? 0) > 0) {
        return;
    }
    menu_insert($db, null, 'خانه', '/', 'صفحه اصلی کارخانه', 10, 0);
    $machines = menu_insert($db, null, 'ماشین‌ها', '/machines', 'ادوات خاک‌ورزی، کاشت، داشت و حمل', 20, 1);
    $lines = menu_insert($db, $machines, 'خطوط تولید', '', 'مدل‌ها را با نوع کار زمین ببینید', 10, 0);
    menu_insert($db, $lines, 'خاک‌ورزی', '/machines?line=tillage', 'گاوآهن، دیسک، چیزل و روتیواتور', 10, 0);
    menu_insert($db, $lines, 'کاشت', '/machines?line=seeding', 'خطی‌کار و ردیف‌کار', 20, 0);
    menu_insert($db, $lines, 'داشت', '/machines?line=care', 'سمپاش و کودپاش', 30, 0);
    menu_insert($db, $lines, 'برداشت و حمل', '/machines?line=harvest', 'دروگر و تریلر', 40, 0);
    $company = menu_insert($db, null, 'شرکت', '/about', 'کارخانه و راه‌های همکاری', 30, 1);
    menu_insert($db, $company, 'کارخانه', '/about', 'توان ساخت و خط تولید', 10, 0);
    menu_insert($db, $company, 'تماس و پیش‌فاکتور', '/contact', 'درخواست برای همین مدل', 20, 0);
    menu_insert($db, $company, 'ثبت‌نام', '/register', 'مشتری یا درخواست نمایندگی', 30, 0);
    menu_insert($db, $company, 'استعلام نمایندگی', '/estelam', 'بررسی نمایندگی شهرستان', 40, 0);
}

function menu_insert(Database $db, ?int $parent, string $label, string $href, string $description, int $order, int $mega): int
{
    $db->run(
        'INSERT INTO ak_menu_items (parent_id, label, href, description, sort_order, visible, new_tab, mega) VALUES (?, ?, ?, ?, ?, 1, 0, ?)',
        [$parent, $label, $href, $description, $order, $parent === null ? $mega : 0]
    );
    return (int) $db->id();
}

/** @return list<array<string, mixed>> */
function menu_rows(App $app): array
{
    menu_extend($app);
    return $app->db->all('SELECT * FROM ak_menu_items ORDER BY sort_order, id');
}

function menu_extend(App $app): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $columns = [
        'icon' => 'VARCHAR(20) NOT NULL DEFAULT ""',
        'badge' => 'VARCHAR(24) NOT NULL DEFAULT ""',
        'featured' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ];
    foreach ($columns as $name => $definition) {
        $found = $app->db->one(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_menu_items" AND COLUMN_NAME = ?',
            [$name]
        );
        if (!$found) {
            $app->db->exec('ALTER TABLE ak_menu_items ADD COLUMN ' . $name . ' ' . $definition);
        }
    }
    $icons = [
        '/' => 'home',
        '/machines' => 'box',
        '/about' => 'factory',
        '/contact' => 'phone',
        '/register' => 'plus',
        '/estelam' => 'pin',
    ];
    foreach ($icons as $href => $icon) {
        $app->db->run('UPDATE ak_menu_items SET icon = ? WHERE href = ? AND icon = ""', [$icon, $href]);
    }
    $featured = $app->db->one('SELECT id FROM ak_menu_items WHERE featured = 1 LIMIT 1');
    if (!$featured) {
        $app->db->run('UPDATE ak_menu_items SET featured = 1 WHERE label = ? AND parent_id IS NOT NULL', ['خطوط تولید']);
    }
    $ready = true;
}

/** @return array<string, string> */
function menu_icon_labels(): array
{
    return [
        '' => 'بدون نشانه',
        'home' => 'خانه',
        'box' => 'جعبه',
        'factory' => 'کارخانه',
        'pin' => 'مکان',
        'file' => 'سند',
        'phone' => 'تلفن',
        'user' => 'کاربر',
        'plus' => 'افزودن',
        'key' => 'کلید',
    ];
}

/** @param list<array<string, mixed>> $rows */
function menu_depth(array $rows, int $id): int
{
    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = $row;
    }
    $depth = 0;
    $guard = 0;
    while ($guard < 8 && isset($byId[$id])) {
        $parent = (int) ($byId[$id]['parent_id'] ?? 0);
        if ($parent < 1 || !isset($byId[$parent])) {
            break;
        }
        $depth++;
        $id = $parent;
        $guard++;
    }
    return $depth;
}

/** @param list<array<string, mixed>> $rows */
function menu_below(array $rows, int $id): int
{
    $max = 0;
    foreach ($rows as $row) {
        if ((int) ($row['parent_id'] ?? 0) === $id) {
            $max = max($max, 1 + menu_below($rows, (int) $row['id']));
        }
    }
    return $max;
}

/** @param list<array<string, mixed>> $rows @return list<int> */
function menu_descendant_ids(array $rows, int $id): array
{
    $out = [];
    foreach ($rows as $row) {
        if ((int) ($row['parent_id'] ?? 0) !== $id) {
            continue;
        }
        $child = (int) $row['id'];
        $out[] = $child;
        $out = array_merge($out, menu_descendant_ids($rows, $child));
    }
    return $out;
}

function menu_clean_href(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return mb_substr($value, 0, 240);
    }
    if (str_starts_with($value, '/')) {
        return mb_substr($value, 0, 240);
    }
    return null;
}

/** @param list<array<string, mixed>> $rows @return list<array<string, mixed>> */
function menu_tree_from(array $rows, int $parent, bool $onlyVisible): array
{
    $tree = [];
    foreach ($rows as $row) {
        if ((int) ($row['parent_id'] ?? 0) !== $parent) {
            continue;
        }
        if ($onlyVisible && (int) $row['visible'] !== 1) {
            continue;
        }
        $row['children'] = menu_tree_from($rows, (int) $row['id'], $onlyVisible);
        $tree[] = $row;
    }
    return $tree;
}

/** @return list<array<string, mixed>> */
function menu_public_tree(App $app): array
{
    return menu_merge_pages($app, menu_tree_from(menu_rows($app), 0, true));
}

function menu_page_href(string $slug): string
{
    if ($slug === '' || $slug === 'home') {
        return '/';
    }
    return '/' . $slug;
}

/** @param list<array<string, mixed>> $nodes @param array<string, true> $known */
function menu_collect_hrefs(array $nodes, array &$known): void
{
    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $href = (string) ($node['href'] ?? '');
        if ($href !== '') {
            $known[$href] = true;
        }
        $children = $node['children'] ?? [];
        if (is_array($children)) {
            menu_collect_hrefs($children, $known);
        }
    }
}

/** @param list<array<string, mixed>> $tree @return list<array<string, mixed>> */
function menu_merge_pages(App $app, array $tree): array
{
    try {
        $pages = $app->db->all(
            'SELECT title, slug FROM ak_pages WHERE status = "published" AND in_menu = 1 ORDER BY menu_order, id'
        );
    } catch (Throwable) {
        return $tree;
    }
    $known = [];
    menu_collect_hrefs($tree, $known);
    foreach ($pages as $page) {
        $href = menu_page_href((string) $page['slug']);
        if (isset($known[$href])) {
            continue;
        }
        $tree[] = [
            'label' => (string) $page['title'],
            'href' => $href,
            'description' => '',
            'children' => [],
            'visible' => 1,
            'new_tab' => 0,
            'mega' => 0,
            'icon' => '',
            'badge' => '',
            'featured' => 0,
        ];
        $known[$href] = true;
    }
    return $tree;
}

function menu_sync_page(App $app, string $oldSlug, string $newSlug, string $oldTitle, string $newTitle, bool $show, int $order): void
{
    menu_extend($app);
    $newHref = menu_page_href($newSlug);
    if ($oldSlug !== '' && $oldSlug !== $newSlug && $oldSlug !== 'home') {
        $app->db->run(
            'UPDATE ak_menu_items SET href = ? WHERE href = ?',
            [$newHref, menu_page_href($oldSlug)]
        );
    }
    if (!$show) {
        return;
    }
    $rows = menu_rows($app);
    $hrefTaken = false;
    foreach ($rows as $row) {
        if ((string) $row['href'] !== $newHref) {
            continue;
        }
        $hrefTaken = true;
        $label = (string) $row['label'];
        if ($newTitle === '' || $label !== $oldTitle || $newTitle === $oldTitle) {
            continue;
        }
        $app->db->run(
            'UPDATE ak_menu_items SET label = ?, href = ? WHERE id = ?',
            [$newTitle, $newHref, (int) $row['id']]
        );
    }
    if ($hrefTaken) {
        return;
    }
    $app->db->run(
        'INSERT INTO ak_menu_items (parent_id, label, href, description, sort_order, visible, new_tab, mega) VALUES (NULL, ?, ?, "", ?, 1, 0, 0)',
        [$newTitle, $newHref, $order]
    );
}

/** @return array{effect: string, open: string} */
function menu_options(App $app): array
{
    $effect = setting($app, 'menus', 'effect', 'fade');
    $open = setting($app, 'menus', 'open', 'hover');
    if (!in_array($effect, ['none', 'fade', 'slide'], true)) {
        $effect = 'fade';
    }
    if (!in_array($open, ['hover', 'click'], true)) {
        $open = 'hover';
    }
    return ['effect' => $effect, 'open' => $open];
}

function menu_save_options(App $app): void
{
    $effect = post_string('effect', 12);
    $open = post_string('open', 12);
    if (!in_array($effect, ['none', 'fade', 'slide'], true)) {
        $effect = 'fade';
    }
    if (!in_array($open, ['hover', 'click'], true)) {
        $open = 'hover';
    }
    save_setting($app, 'menus', 'effect', $effect);
    save_setting($app, 'menus', 'open', $open);
    flash('success', 'تنظیم منو ذخیره شد.');
    redirect('/admin/menus');
}

function menu_save(App $app): void
{
    $rows = menu_rows($app);
    $id = (int) post_string('id', 12);
    $current = null;
    foreach ($rows as $row) {
        if ((int) $row['id'] === $id) {
            $current = $row;
        }
    }
    $label = post_string('label', 80);
    $href = menu_clean_href(post_string('href', 240));
    $description = post_string('description', 180);
    $parentId = (int) post_string('parent_id', 12);
    $order = max(-999, min(9999, (int) post_string('sort_order', 6)));
    if ($label === '' || $href === null) {
        flash('error', 'عنوان لازم است و پیوند باید با / یا http شروع شود. برای عنوان ستون، پیوند را خالی بگذارید.');
        redirect('/admin/menus');
    }
    if ($id > 0 && !$current) {
        flash('error', 'این آیتم پیدا نشد.');
        redirect('/admin/menus');
    }
    if ($parentId > 0) {
        $parentExists = false;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $parentId) {
                $parentExists = true;
            }
        }
        if (!$parentExists) {
            flash('error', 'جایگاه انتخاب‌شده پیدا نشد.');
            redirect('/admin/menus');
        }
    }
    $blocked = $id > 0 ? array_merge([$id], menu_descendant_ids($rows, $id)) : [];
    if ($parentId > 0 && in_array($parentId, $blocked, true)) {
        flash('error', 'آیتم نمی‌تواند زیرمجموعه خودش باشد.');
        redirect('/admin/menus');
    }
    $parentDepth = $parentId > 0 ? menu_depth($rows, $parentId) : -1;
    if ($parentId > 0 && $parentDepth > 1) {
        flash('error', 'زیرمنو حداکثر تا ستون و پیوند داخل آن است.');
        redirect('/admin/menus');
    }
    $below = $id > 0 ? menu_below($rows, $id) : 0;
    $newDepth = $parentId === 0 ? 0 : $parentDepth + 1;
    if ($newDepth + $below > 2) {
        flash('error', 'این جابه‌جایی از سه سطح منو، ستون و پیوند عمیق‌تر می‌شود.');
        redirect('/admin/menus');
    }
    if ($newDepth === 0 && $href === '') {
        flash('error', 'آیتم اصلی منو باید پیوند داشته باشد.');
        redirect('/admin/menus');
    }
    $visible = isset($_POST['visible']) ? 1 : 0;
    $newTab = isset($_POST['new_tab']) ? 1 : 0;
    $mega = $newDepth === 0 && isset($_POST['mega']) ? 1 : 0;
    $icon = post_string('icon', 20);
    if (!array_key_exists($icon, menu_icon_labels())) {
        $icon = '';
    }
    $badge = post_string('badge', 24);
    $featured = $newDepth === 1 && isset($_POST['featured']) ? 1 : 0;
    $parentValue = $parentId > 0 ? $parentId : null;
    if ($id > 0) {
        $app->db->run(
            'UPDATE ak_menu_items SET parent_id=?, label=?, href=?, description=?, sort_order=?, visible=?, new_tab=?, mega=?, icon=?, badge=?, featured=? WHERE id=?',
            [$parentValue, $label, $href, $description, $order, $visible, $newTab, $mega, $icon, $badge, $featured, $id]
        );
        flash('success', 'آیتم منو ذخیره شد.');
    } else {
        $app->db->run(
            'INSERT INTO ak_menu_items (parent_id, label, href, description, sort_order, visible, new_tab, mega, icon, badge, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$parentValue, $label, $href, $description, $order, $visible, $newTab, $mega, $icon, $badge, $featured]
        );
        flash('success', 'آیتم به منو اضافه شد.');
    }
    redirect('/admin/menus');
}

function menu_delete(App $app): void
{
    $rows = menu_rows($app);
    $id = (int) post_string('id', 12);
    $exists = false;
    foreach ($rows as $row) {
        if ((int) $row['id'] === $id) {
            $exists = true;
        }
    }
    if (!$exists) {
        flash('error', 'این آیتم پیدا نشد.');
        redirect('/admin/menus');
    }
    $ids = array_merge([$id], menu_descendant_ids($rows, $id));
    $ids = array_values(array_filter($ids, static fn (int $item): bool => $item > 0));
    if ($ids === []) {
        flash('error', 'آیتمی برای حذف نیست.');
        redirect('/admin/menus');
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $app->db->run('DELETE FROM ak_menu_items WHERE id IN (' . $marks . ')', $ids);
    flash('success', 'آیتم و زیرمجموعه‌اش حذف شد.');
    redirect('/admin/menus');
}

function menu_import_pages(App $app): void
{
    try {
        $pages = $app->db->all('SELECT title, slug FROM ak_pages WHERE status = "published" AND in_menu = 1 ORDER BY menu_order, id');
    } catch (Throwable) {
        flash('error', 'فهرست صفحه‌ها در دسترس نیست.');
        redirect('/admin/menus');
    }
    $rows = menu_rows($app);
    $known = [];
    foreach ($rows as $row) {
        $known[(string) $row['href']] = true;
    }
    $added = 0;
    $order = 80;
    foreach ($pages as $page) {
        $slug = (string) $page['slug'];
        $href = $slug === 'home' ? '/' : '/' . $slug;
        if (isset($known[$href])) {
            continue;
        }
        menu_insert($app->db, null, (string) $page['title'], $href, '', $order, 0);
        $known[$href] = true;
        $order += 10;
        $added++;
    }
    flash('success', $added > 0 ? fa_digits((string) $added) . ' صفحه به منوی اصلی اضافه شد.' : 'صفحه تازه‌ای برای افزودن نبود.');
    redirect('/admin/menus');
}
