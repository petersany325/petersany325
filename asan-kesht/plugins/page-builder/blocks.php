<?php

declare(strict_types=1);

function pb_definitions(): array
{
    $text = static fn (string $label): array => ['label' => $label, 'type' => 'text'];
    $area = static fn (string $label): array => ['label' => $label, 'type' => 'textarea'];
    return [
        'hero' => ['label' => 'سرصفحه', 'fields' => [
            'title' => $text('عنوان'), 'text' => $area('متن'),
            'button_label' => $text('متن دکمه'), 'button_url' => $text('پیوند دکمه'),
        ]],
        'text' => ['label' => 'متن', 'fields' => ['title' => $text('عنوان'), 'body' => $area('متن')]],
        'features' => ['label' => 'چهار قابلیت', 'fields' => ['title' => $text('عنوان'), 'items' => ['label' => 'موارد', 'type' => 'pairs', 'count' => 4]]],
        'machines' => ['label' => 'ماشین‌ها', 'fields' => ['title' => $text('عنوان'), 'limit' => $text('تعداد')]],
        'steps' => ['label' => 'مراحل', 'fields' => ['title' => $text('عنوان'), 'items' => ['label' => 'مراحل', 'type' => 'pairs', 'count' => 5]]],
        'cta' => ['label' => 'فراخوان', 'fields' => [
            'title' => $text('عنوان'), 'text' => $area('متن'),
            'button_label' => $text('متن دکمه'), 'button_url' => $text('پیوند'),
        ]],
        'faq' => ['label' => 'پرسش‌ها', 'fields' => ['title' => $text('عنوان'), 'items' => ['label' => 'پرسش و پاسخ', 'type' => 'pairs', 'count' => 4]]],
        'columns' => ['label' => 'ستون‌ها', 'fields' => ['title' => $text('عنوان'), 'items' => ['label' => 'ستون‌ها', 'type' => 'pairs', 'count' => 3]]],
        'quote' => ['label' => 'فرم پیش‌فاکتور', 'fields' => ['title' => $text('عنوان'), 'text' => $area('توضیح')]],
    ];
}

function pb_blank(string $type): array
{
    $def = pb_definitions()[$type] ?? null;
    $data = [];
    if ($def) {
        foreach ($def['fields'] as $name => $field) {
            if (($field['type'] ?? '') === 'pairs') {
                $data[$name] = array_fill(0, (int) $field['count'], ['title' => '', 'text' => '']);
            } else {
                $data[$name] = $name === 'limit' ? '4' : '';
            }
        }
    }
    return ['type' => $type, 'hidden' => 0, 'anchor' => '', 'data' => $data];
}

function pb_from_post(array $posted): array
{
    $defs = pb_definitions();
    $blocks = [];
    foreach ($posted as $row) {
        if (!is_array($row)) {
            continue;
        }
        $type = (string) ($row['type'] ?? '');
        if (!isset($defs[$type])) {
            continue;
        }
        $data = [];
        foreach ($defs[$type]['fields'] as $name => $field) {
            $raw = $row['data'][$name] ?? '';
            if (($field['type'] ?? '') === 'pairs') {
                $pairs = [];
                $items = is_array($raw) ? $raw : [];
                for ($i = 0; $i < (int) $field['count']; $i++) {
                    $item = $items[$i] ?? [];
                    $pairs[] = [
                        'title' => mb_substr(trim((string) ($item['title'] ?? '')), 0, 160),
                        'text' => mb_substr(trim((string) ($item['text'] ?? '')), 0, 800),
                    ];
                }
                $data[$name] = $pairs;
            } else {
                $data[$name] = mb_substr(trim((string) $raw), 0, 4000);
            }
        }
        $blocks[] = [
            'type' => $type,
            'hidden' => !empty($row['hidden']) ? 1 : 0,
            'anchor' => slugify((string) ($row['anchor'] ?? '')),
            'data' => $data,
        ];
    }
    return $blocks;
}

function pb_render(App $app, array $blocks): string
{
    $html = '';
    foreach ($blocks as $block) {
        if (!is_array($block) || !empty($block['hidden'])) {
            continue;
        }
        $filtered = $app->filter('block.render', ['block' => $block, 'html' => null]);
        if (is_array($filtered) && is_string($filtered['html'])) {
            $html .= $filtered['html'];
            continue;
        }
        $html .= pb_render_builtin($block);
    }
    return $html;
}

function pb_render_builtin(array $block): string
{
    $type = (string) ($block['type'] ?? '');
    $data = is_array($block['data'] ?? null) ? $block['data'] : [];
    $anchor = (string) ($block['anchor'] ?? '');
    $id = $anchor !== '' ? ' id="' . e($anchor) . '"' : '';
    $title = (string) ($data['title'] ?? '');
    if ($type === 'hero') {
        return '<section class="wrap hero"' . $id . '><div><p class="kicker">خط تولید ادوات</p><h1>' . e($title) . '</h1><p class="lead">' . e((string) ($data['text'] ?? '')) . '</p>'
            . pb_button((string) ($data['button_label'] ?? ''), (string) ($data['button_url'] ?? ''))
            . '</div><div class="plate" aria-hidden="true"><svg viewBox="0 0 640 360"><path d="M40 250h560" stroke="#c6a15a" stroke-width="2"/><path d="M70 230l40-70h70l30 70" fill="none" stroke="#f3efe4" stroke-width="3"/><path d="M150 200h360" stroke="#f3efe4" stroke-width="6"/><path d="M230 200c10 40 30 70 70 80" fill="none" stroke="#d24a1a" stroke-width="8"/><path d="M340 205c12 36 34 62 74 72" fill="none" stroke="#d24a1a" stroke-width="8"/><path d="M450 208c10 30 28 52 60 60" fill="none" stroke="#d24a1a" stroke-width="8"/><circle cx="120" cy="250" r="28" fill="none" stroke="#c6a15a" stroke-width="4"/><circle cx="530" cy="248" r="22" fill="none" stroke="#c6a15a" stroke-width="4"/></svg><div class="titleblock"><span>نقشه جانبی</span><b class="code">AK-MP</b><span>گاوآهن برگرداندار</span><span>مقیاس آزاد</span></div></div></section>';
    }
    if ($type === 'text') {
        return '<section class="section"' . $id . '><div class="wrap"><h2>' . e($title) . '</h2><p class="lead">' . nl2br(e((string) ($data['body'] ?? ''))) . '</p></div></section>';
    }
    if ($type === 'features' || $type === 'columns') {
        $class = $type === 'features' ? 'lines' : 'quality';
        $cards = '';
        $tones = ['line line-dark', 'line', 'line line-sand', 'line line-oxide'];
        foreach ($data['items'] ?? [] as $i => $item) {
            if (!is_array($item) || (($item['title'] ?? '') === '' && ($item['text'] ?? '') === '')) {
                continue;
            }
            $tone = $tones[$i % count($tones)];
            $cards .= '<article class="' . $tone . '"><em class="code">' . e(fa_digits(sprintf('%02d', $i + 1))) . '</em><h3>' . e((string) $item['title']) . '</h3><p>' . e((string) $item['text']) . '</p></article>';
        }
        return '<section class="section"' . $id . '><div class="wrap"><div class="section-head"><h2>' . e($title) . '</h2></div><div class="' . $class . '">' . $cards . '</div></div></section>';
    }
    if ($type === 'steps') {
        $steps = '';
        foreach ($data['items'] ?? [] as $item) {
            if (!is_array($item) || ($item['title'] ?? '') === '') {
                continue;
            }
            $steps .= '<article class="step"><h3>' . e((string) $item['title']) . '</h3><p>' . e((string) $item['text']) . '</p></article>';
        }
        return '<section class="section"' . $id . '><div class="wrap"><div class="section-head"><h2>' . e($title) . '</h2></div><div class="process">' . $steps . '</div></div></section>';
    }
    if ($type === 'cta') {
        return '<section class="band section"' . $id . '><div class="wrap band-grid"><div><h2>' . e($title) . '</h2><p class="lead">' . e((string) ($data['text'] ?? '')) . '</p></div><div>' . pb_button((string) ($data['button_label'] ?? ''), (string) ($data['button_url'] ?? '')) . '</div></div></section>';
    }
    if ($type === 'faq') {
        $items = '';
        foreach ($data['items'] ?? [] as $item) {
            if (!is_array($item) || ($item['title'] ?? '') === '') {
                continue;
            }
            $items .= '<details class="sheet"><summary><h3>' . e((string) $item['title']) . '</h3></summary><p>' . e((string) $item['text']) . '</p></details>';
        }
        return '<section class="section"' . $id . '><div class="wrap"><h2>' . e($title) . '</h2><div class="sheets">' . $items . '</div></div></section>';
    }
    return '';
}

function pb_button(string $label, string $href): string
{
    if ($label === '' || $href === '') {
        return '';
    }
    $safe = str_starts_with($href, '/') ? url($href) : $href;
    if (!str_starts_with($href, '/') && !preg_match('#^https?://#', $href)) {
        return '';
    }
    return '<p class="actions"><a class="btn" href="' . e($safe) . '">' . e($label) . '</a></p>';
}
