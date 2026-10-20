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
        'catalog' => ['label' => 'ویترین محصولات', 'fields' => [
            'kicker' => $text('شعار کوتاه'),
            'title' => $text('عنوان'),
            'text' => $area('متن'),
            'button_label' => $text('متن دکمه'),
            'button_url' => $text('پیوند دکمه'),
            'items' => ['label' => 'سه گروه محصول', 'type' => 'pairs', 'count' => 3],
            'parts_title' => $text('عنوان قطعات'),
            'parts_text' => $text('توضیح قطعات'),
            'parts_url' => $text('پیوند قطعات'),
            'dealers_title' => $text('عنوان نمایندگی'),
            'dealers_text' => $text('توضیح نمایندگی'),
            'dealers_url' => $text('پیوند نمایندگی'),
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
        $html .= pb_render_builtin($app, $block);
    }
    return $html;
}

function pb_render_builtin(App $app, array $block): string
{
    $type = (string) ($block['type'] ?? '');
    $data = is_array($block['data'] ?? null) ? $block['data'] : [];
    $anchor = (string) ($block['anchor'] ?? '');
    $id = $anchor !== '' ? ' id="' . e($anchor) . '"' : '';
    $title = (string) ($data['title'] ?? '');
    if ($type === 'catalog') {
        return pb_render_catalog($app, $data, $id);
    }
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
    $safe = pb_safe_href($href);
    if ($label === '' || $safe === '') {
        return '';
    }
    return '<p class="actions"><a class="btn" href="' . e($safe) . '">' . e($label) . '</a></p>';
}

function pb_safe_href(string $href): string
{
    if ($href === '') {
        return '';
    }
    if (str_starts_with($href, '/')) {
        return url($href);
    }
    if (preg_match('#^https?://#', $href)) {
        return $href;
    }
    return '';
}

function pb_catalog_data(): array
{
    return [
        'kicker' => 'قدرت امروز، آبادانی فردا',
        'title' => 'ماشین‌آلات کشاورزی و صنعتی',
        'text' => 'ادوات خاک‌ورزی، کاشت، داشت و برداشت برای تراکتورهای رایج. شاسی، عرض کار و توان، پیش از ساخت مشخص می‌شود.',
        'button_label' => 'مشاهده محصولات',
        'button_url' => '/machines',
        'items' => [
            ['title' => 'خاک‌ورزی', 'text' => 'گاوآهن، دیسک، چیزل و زیرشکن'],
            ['title' => 'کاشت', 'text' => 'خطی‌کار غلات و ردیف‌کار'],
            ['title' => 'داشت و برداشت', 'text' => 'سمپاش بوم‌دار، کودپاش و دروگر'],
        ],
        'parts_title' => 'قطعات و خدمات پس از فروش',
        'parts_text' => 'قطعه سایشی مدل‌های جاری و پاسخ به درخواست سرویس',
        'parts_url' => '/contact',
        'dealers_title' => 'شبکه نمایندگی‌ها',
        'dealers_text' => 'تأمین ادوات در سراسر کشور',
        'dealers_url' => '/estelam',
    ];
}

function pb_render_catalog(App $app, array $data, string $id): string
{
    $button = pb_safe_href((string) ($data['button_url'] ?? ''));
    $buttonLabel = (string) ($data['button_label'] ?? '');
    $cards = '';
    $arts = [pb_art_tillage(), pb_art_seeder(), pb_art_sprayer()];
    $fallbackLines = ['tillage', 'seeding', 'care', 'harvest'];
    $lineByTitle = [
        'خاک‌ورزی' => 'tillage',
        'کاشت' => 'seeding',
        'داشت' => 'care',
        'داشت و برداشت' => 'care',
        'برداشت' => 'harvest',
        'برداشت و حمل' => 'harvest',
    ];
    $index = 0;
    foreach ($data['items'] ?? [] as $item) {
        if (!is_array($item) || (($item['title'] ?? '') === '' && ($item['text'] ?? '') === '')) {
            $index++;
            continue;
        }
        $title = (string) ($item['title'] ?? '');
        $line = $lineByTitle[$title] ?? ($fallbackLines[$index] ?? 'tillage');
        $image = function_exists('catalog_line_image') ? catalog_line_image($app, $line) : '';
        if ($image !== '') {
            $art = '<img src="' . e(url('/catalog/photo/' . $image)) . '" alt="' . e($title) . '">';
        } else {
            $art = $arts[$index] ?? pb_art_tillage();
        }
        $link = '';
        if ($button !== '' && $buttonLabel !== '') {
            $link = '<a href="' . e($button) . '">' . e($buttonLabel) . '</a>';
        }
        $cards .= '<article class="yard-card"><div class="yard-art">' . $art . '</div><h3>' . e($title) . '</h3><p>' . e((string) $item['text']) . '</p>' . $link . '</article>';
        $index++;
    }
    $partsHref = pb_safe_href((string) ($data['parts_url'] ?? ''));
    $dealersHref = pb_safe_href((string) ($data['dealers_url'] ?? ''));
    $partsLink = $partsHref !== '' ? '<a href="' . e($partsHref) . '">مشاهده قطعات و خدمات</a>' : '';
    $dealersLink = $dealersHref !== '' ? '<a href="' . e($dealersHref) . '">مشاهده نمایندگی‌ها</a>' : '';
    $heroBtn = ($button !== '' && $buttonLabel !== '')
        ? '<p class="actions"><a class="btn" href="' . e($button) . '">' . e($buttonLabel) . '</a></p>'
        : '';
    return '<section class="yard"' . $id . '>'
        . '<div class="yard-hero"><div class="yard-scene" aria-hidden="true">' . pb_art_field() . '</div>'
        . '<div class="wrap yard-copy"><p class="yard-kicker">' . e((string) ($data['kicker'] ?? '')) . '</p><h1>' . e((string) ($data['title'] ?? '')) . '</h1><p class="lead">' . e((string) ($data['text'] ?? '')) . '</p>' . $heroBtn . '</div></div>'
        . '<div class="wrap yard-cards">' . $cards . '</div>'
        . '<div class="wrap yard-lower">'
        . '<article class="yard-panel"><div class="yard-art">' . pb_art_parts() . '</div><div><h3>' . e((string) ($data['parts_title'] ?? '')) . '</h3><p>' . e((string) ($data['parts_text'] ?? '')) . '</p>' . $partsLink . '</div></article>'
        . '<article class="yard-panel"><div class="yard-art">' . pb_art_map() . '</div><div><h3>' . e((string) ($data['dealers_title'] ?? '')) . '</h3><p>' . e((string) ($data['dealers_text'] ?? '')) . '</p>' . $dealersLink . '</div></article>'
        . '</div></section>';
}

function pb_art_field(): string
{
    return '<svg viewBox="0 0 1200 480" preserveAspectRatio="xMidYMid slice" role="img"><defs><linearGradient id="yardSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7ea6c8"/><stop offset="1" stop-color="#e7eef5"/></linearGradient><linearGradient id="yardGround" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d2bc86"/><stop offset="1" stop-color="#7f8f58"/></linearGradient></defs>'
        . '<rect width="1200" height="480" fill="url(#yardSky)"/>'
        . '<circle cx="210" cy="78" r="34" fill="#e2b000"/>'
        . '<ellipse cx="390" cy="70" rx="54" ry="16" fill="#fff" opacity=".75"/><ellipse cx="430" cy="62" rx="36" ry="14" fill="#fff" opacity=".8"/>'
        . '<g fill="#d7dee6" stroke="#17304f" stroke-width="2"><rect x="64" y="168" width="42" height="132"/><rect x="118" y="132" width="52" height="168"/><rect x="182" y="176" width="40" height="124"/><path d="M118 132 L144 96 L170 132" fill="#e2b000"/></g>'
        . '<path d="M0 292 C220 250 420 330 700 286 C920 252 1060 310 1200 268 L1200 480 L0 480 Z" fill="url(#yardGround)"/>'
        . '<path d="M0 348 H1200 M0 382 H1200" stroke="#5e6c40" stroke-width="3" opacity=".28"/>'
        . '<g transform="translate(390 236)"><rect x="8" y="46" width="300" height="12" rx="3" fill="#17304f"/><rect x="48" y="8" width="14" height="48" fill="#e2b000"/><rect x="230" y="14" width="14" height="42" fill="#e2b000"/>'
        . '<g fill="#3e4856"><ellipse cx="36" cy="78" rx="9" ry="26"/><ellipse cx="64" cy="78" rx="9" ry="26"/><ellipse cx="92" cy="78" rx="9" ry="26"/><ellipse cx="120" cy="78" rx="9" ry="26"/><ellipse cx="168" cy="78" rx="9" ry="26"/><ellipse cx="196" cy="78" rx="9" ry="26"/><ellipse cx="224" cy="78" rx="9" ry="26"/><ellipse cx="252" cy="78" rx="9" ry="26"/></g>'
        . '<circle cx="28" cy="96" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/><circle cx="286" cy="96" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/></g>'
        . '<g transform="translate(760 228)" stroke-linecap="round"><path d="M24 28 H210" stroke="#17304f" stroke-width="10"/><path d="M48 28 L30 118" stroke="#e2b000" stroke-width="8"/><path d="M92 28 L78 124" stroke="#17304f" stroke-width="8"/><path d="M136 28 L128 124" stroke="#17304f" stroke-width="8"/><path d="M180 28 L176 112" stroke="#e2b000" stroke-width="8"/>'
        . '<circle cx="36" cy="124" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/><circle cx="196" cy="120" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/></g></svg>';
}

function pb_art_tillage(): string
{
    return '<svg viewBox="0 0 320 150" role="img"><rect width="320" height="150" fill="#eef2f6"/>'
        . '<path d="M36 96 H168" stroke="#17304f" stroke-width="8" stroke-linecap="round"/>'
        . '<path d="M168 96 L214 52" stroke="#e2b000" stroke-width="8" stroke-linecap="round"/>'
        . '<path d="M78 96 L62 118 H156 L176 96 Z" fill="#3e4856"/>'
        . '<path d="M214 52 L248 78 L214 96 Z" fill="#e2b000" stroke="#172033" stroke-width="2"/>'
        . '<circle cx="58" cy="118" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
        . '<circle cx="248" cy="104" r="14" fill="#e2b000" stroke="#172033" stroke-width="3"/></svg>';
}

function pb_art_seeder(): string
{
    return '<svg viewBox="0 0 320 150" role="img"><rect width="320" height="150" fill="#eef2f6"/>'
        . '<rect x="40" y="78" width="230" height="10" rx="3" fill="#17304f"/>'
        . '<path d="M78 78 L70 42 H118 L110 78 Z" fill="#e2b000" stroke="#172033" stroke-width="2"/>'
        . '<path d="M132 78 L124 36 H176 L168 78 Z" fill="#e2b000" stroke="#172033" stroke-width="2"/>'
        . '<path d="M190 78 L182 42 H230 L222 78 Z" fill="#e2b000" stroke="#172033" stroke-width="2"/>'
        . '<path d="M94 88 V118 M154 88 V118 M206 88 V118" stroke="#3e4856" stroke-width="4"/>'
        . '<circle cx="52" cy="112" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
        . '<circle cx="258" cy="112" r="16" fill="#e2b000" stroke="#172033" stroke-width="3"/></svg>';
}

function pb_art_sprayer(): string
{
    return '<svg viewBox="0 0 320 150" role="img"><rect width="320" height="150" fill="#eef2f6"/>'
        . '<rect x="118" y="48" width="78" height="48" rx="16" fill="#e2b000" stroke="#172033" stroke-width="2"/>'
        . '<rect x="28" y="62" width="250" height="8" rx="3" fill="#17304f"/>'
        . '<path d="M48 70 V96 M92 70 V96 M156 96 V118 M210 70 V96 M258 70 V96" stroke="#3e4856" stroke-width="4"/>'
        . '<circle cx="48" cy="100" r="5" fill="#17304f"/><circle cx="92" cy="100" r="5" fill="#17304f"/><circle cx="210" cy="100" r="5" fill="#17304f"/><circle cx="258" cy="100" r="5" fill="#17304f"/>'
        . '<circle cx="70" cy="118" r="15" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
        . '<circle cx="236" cy="118" r="15" fill="#e2b000" stroke="#172033" stroke-width="3"/></svg>';
}

function pb_art_parts(): string
{
    return '<svg viewBox="0 0 200 140" role="img"><rect width="200" height="140" fill="#eef2f6"/>'
        . '<ellipse cx="58" cy="70" rx="28" ry="28" fill="none" stroke="#17304f" stroke-width="10"/>'
        . '<ellipse cx="58" cy="70" rx="10" ry="10" fill="#e2b000"/>'
        . '<rect x="108" y="42" width="28" height="62" rx="8" fill="#3e4856"/>'
        . '<rect x="146" y="34" width="22" height="74" rx="8" fill="#e2b000" stroke="#172033" stroke-width="2"/></svg>';
}

function pb_art_map(): string
{
    return '<svg viewBox="0 0 200 140" role="img"><rect width="200" height="140" fill="#eef2f6"/>'
        . '<path d="M108 16 L132 22 L148 18 L164 36 L176 48 L170 70 L156 78 L162 98 L146 112 L124 124 L108 112 L96 96 L78 92 L62 78 L54 58 L66 40 L84 30 L100 22 Z" fill="#d5dde6" stroke="#17304f" stroke-width="2"/>'
        . '<g fill="#e2b000" stroke="#172033" stroke-width="1.4"><circle cx="118" cy="62" r="4"/><circle cx="132" cy="78" r="4"/><circle cx="108" cy="96" r="4"/><circle cx="146" cy="48" r="4"/><circle cx="96" cy="58" r="4"/></g></svg>';
}
