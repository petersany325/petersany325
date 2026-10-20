<?php

declare(strict_types=1);

/** @return array<string, string> */
function layout_defaults(): array
{
    return [
        'structure' => 'full',
        'notice_on' => '0',
        'notice_text' => '',
        'notice_label' => '',
        'notice_url' => '',
        'footer_about' => '',
        'footer_note' => '',
        'footer_show_menu' => '1',
        'footer_col1' => 'خدمات',
        'footer_col2' => 'همکاری',
        'footer_links_1' => '',
        'footer_links_2' => '',
    ];
}

/** @return array<string, string> */
function layout_values(App $app): array
{
    $out = [];
    foreach (layout_defaults() as $key => $default) {
        $out[$key] = setting($app, 'layout', $key, $default);
    }
    return $out;
}

function layout_clean_href(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value) === 1) {
        return mb_substr($value, 0, 240);
    }
    if (str_starts_with($value, '/')) {
        return mb_substr($value, 0, 240);
    }
    return '';
}

/** @return list<array{label:string,href:string}> */
function layout_links(string $raw): array
{
    $decoded = json_decode($raw, true);
    $out = [];
    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            if (!is_array($row) || count($out) >= 4) {
                continue;
            }
            $out[] = [
                'label' => (string) ($row['label'] ?? ''),
                'href' => (string) ($row['href'] ?? ''),
            ];
        }
    }
    while (count($out) < 4) {
        $out[] = ['label' => '', 'href' => ''];
    }
    return $out;
}

/** @return array{id:int,blocks:list<array<string,mixed>>} */
function layout_home_blocks(App $app): array
{
    try {
        $home = $app->db->one('SELECT id, blocks_json FROM ak_pages WHERE slug = "home"');
    } catch (Throwable) {
        return ['id' => 0, 'blocks' => []];
    }
    if (!$home) {
        return ['id' => 0, 'blocks' => []];
    }
    $blocks = json_decode((string) $home['blocks_json'], true);
    $clean = [];
    if (is_array($blocks)) {
        foreach ($blocks as $block) {
            if (is_array($block)) {
                $clean[] = $block;
            }
        }
    }
    return ['id' => (int) $home['id'], 'blocks' => $clean];
}

/** @param list<array<string, mixed>> $posted */
function layout_collect_links(array $posted): ?array
{
    $clean = [];
    foreach ($posted as $row) {
        if (!is_array($row) || count($clean) >= 4) {
            continue;
        }
        $label = mb_substr(trim((string) ($row['label'] ?? '')), 0, 40);
        $rawHref = trim((string) ($row['href'] ?? ''));
        $href = layout_clean_href($rawHref);
        if ($rawHref !== '' && $href === '') {
            return null;
        }
        $clean[] = ['label' => $label, 'href' => $href];
    }
    while (count($clean) < 4) {
        $clean[] = ['label' => '', 'href' => ''];
    }
    return $clean;
}

function layout_save(App $app): void
{
    $structure = post_string('structure', 12);
    if (!in_array($structure, ['full', 'focus', 'stack'], true)) {
        $structure = 'full';
    }
    $noticeRaw = trim(post_string('notice_url', 240));
    $noticeUrl = layout_clean_href($noticeRaw);
    if ($noticeRaw !== '' && $noticeUrl === '') {
        flash('error', 'پیوند نوار صفحه اول باید با / یا http شروع شود.');
        redirect('/admin/layout');
    }
    $catalogRaw = trim(post_string('catalog_button_url', 240));
    $catalogUrl = layout_clean_href($catalogRaw);
    if ($catalogRaw !== '' && $catalogUrl === '') {
        flash('error', 'پیوند دکمه صفحه اول باید با / یا http شروع شود.');
        redirect('/admin/layout');
    }
    $linkSets = [];
    foreach ([1, 2] as $column) {
        $posted = $_POST['links' . $column] ?? [];
        $clean = layout_collect_links(is_array($posted) ? $posted : []);
        if ($clean === null) {
            flash('error', 'پیوند فوتر باید با / یا http شروع شود.');
            redirect('/admin/layout');
        }
        $linkSets[$column] = $clean;
    }
    save_setting($app, 'layout', 'structure', $structure);
    save_setting($app, 'layout', 'notice_on', isset($_POST['notice_on']) ? '1' : '0');
    save_setting($app, 'layout', 'notice_text', post_string('notice_text', 180));
    save_setting($app, 'layout', 'notice_label', post_string('notice_label', 40));
    save_setting($app, 'layout', 'notice_url', $noticeUrl);
    save_setting($app, 'layout', 'footer_about', post_string('footer_about', 500));
    save_setting($app, 'layout', 'footer_note', post_string('footer_note', 240));
    save_setting($app, 'layout', 'footer_show_menu', isset($_POST['footer_show_menu']) ? '1' : '0');
    save_setting($app, 'layout', 'footer_col1', post_string('footer_col1', 40));
    save_setting($app, 'layout', 'footer_col2', post_string('footer_col2', 40));
    foreach ($linkSets as $column => $clean) {
        save_setting($app, 'layout', 'footer_links_' . $column, json_encode($clean, JSON_UNESCAPED_UNICODE));
    }
    layout_save_home($app, $catalogUrl);
    flash('success', 'چیدمان صفحه اول و فوتر ذخیره شد و روی سایت دیده می‌شود.');
    redirect('/admin/layout');
}

function layout_save_home(App $app, string $catalogUrl): void
{
    $home = layout_home_blocks($app);
    if ($home['id'] < 1) {
        return;
    }
    $posted = $_POST['blocks'] ?? null;
    $postedRows = is_array($posted) ? $posted : [];
    $ordered = [];
    foreach ($home['blocks'] as $index => $block) {
        $row = is_array($postedRows[$index] ?? null) ? $postedRows[$index] : null;
        if (is_array($row)) {
            $block['hidden'] = isset($row['show']) ? 0 : 1;
        }
        $ordered[] = [
            'order' => (int) (is_array($row) ? ($row['order'] ?? $index) : $index),
            'index' => $index,
            'block' => $block,
        ];
    }
    usort($ordered, static function (array $left, array $right): int {
        if ($left['order'] === $right['order']) {
            return $left['index'] <=> $right['index'];
        }
        return $left['order'] <=> $right['order'];
    });
    $next = [];
    $catalogDone = false;
    foreach ($ordered as $item) {
        $block = $item['block'];
        if (!$catalogDone && (string) ($block['type'] ?? '') === 'catalog') {
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $data['kicker'] = post_string('catalog_kicker', 120);
            $data['title'] = post_string('catalog_title', 180);
            $data['text'] = post_string('catalog_text', 500);
            $data['button_label'] = post_string('catalog_button_label', 80);
            $data['button_url'] = $catalogUrl;
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $postedItems = $_POST['catalog_items'] ?? [];
            if (is_array($postedItems)) {
                foreach ([0, 1, 2] as $slot) {
                    $current = is_array($items[$slot] ?? null) ? $items[$slot] : ['title' => '', 'text' => ''];
                    $row = is_array($postedItems[$slot] ?? null) ? $postedItems[$slot] : [];
                    $current['title'] = mb_substr(trim((string) ($row['title'] ?? '')), 0, 80);
                    $current['text'] = mb_substr(trim((string) ($row['text'] ?? '')), 0, 180);
                    $items[$slot] = $current;
                }
            }
            $data['items'] = $items;
            $block['data'] = $data;
            $catalogDone = true;
        }
        $next[] = $block;
    }
    $app->db->run(
        'UPDATE ak_pages SET blocks_json = ?, updated_at = NOW() WHERE id = ?',
        [json_encode($next, JSON_UNESCAPED_UNICODE), $home['id']]
    );
}

/**
 * @return array{structure:string,notice:?array{text:string,label:string,url:string},footer_about:string,footer_note:string,footer_show_menu:bool,columns:list<array{title:string,links:list<array{label:string,href:string}>}>}
 */
function layout_public(App $app): array
{
    $values = layout_values($app);
    $structure = in_array($values['structure'], ['full', 'focus', 'stack'], true) ? $values['structure'] : 'full';
    $notice = null;
    if ($values['notice_on'] === '1' && $values['notice_text'] !== '') {
        $notice = [
            'text' => $values['notice_text'],
            'label' => $values['notice_label'],
            'url' => layout_clean_href($values['notice_url']),
        ];
    }
    $columns = [];
    foreach ([1, 2] as $column) {
        $links = [];
        foreach (layout_links($values['footer_links_' . $column]) as $link) {
            $href = layout_clean_href($link['href']);
            if ($link['label'] !== '' && $href !== '') {
                $links[] = ['label' => $link['label'], 'href' => $href];
            }
        }
        if ($links !== []) {
            $title = $values['footer_col' . $column];
            $columns[] = ['title' => $title !== '' ? $title : 'پیوندها', 'links' => $links];
        }
    }
    return [
        'structure' => $structure,
        'notice' => $notice,
        'footer_about' => $values['footer_about'],
        'footer_note' => $values['footer_note'],
        'footer_show_menu' => $values['footer_show_menu'] === '1',
        'columns' => $columns,
    ];
}
