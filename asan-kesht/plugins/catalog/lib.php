<?php

declare(strict_types=1);

/** @return array<string, string> */
function catalog_lines(): array
{
    return [
        'tillage' => 'خاک‌ورزی',
        'seeding' => 'کاشت',
        'care' => 'داشت',
        'harvest' => 'برداشت و حمل',
    ];
}

function catalog_extend(App $app): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $found = $app->db->one(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "ak_machines" AND COLUMN_NAME = ?',
        ['image']
    );
    if (!$found) {
        $app->db->exec('ALTER TABLE ak_machines ADD COLUMN image VARCHAR(80) NOT NULL DEFAULT ""');
    }
    $ready = true;
}

function catalog_photo_name(string $name): bool
{
    return (bool) preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name);
}

function catalog_line_image(App $app, string $line): string
{
    if (!isset(catalog_lines()[$line])) {
        return '';
    }
    $name = setting($app, 'catalog', 'line_image_' . $line, '');
    return catalog_photo_name($name) ? $name : '';
}

function catalog_store_photo(App $app, string $field): string
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('بارگذاری عکس ناموفق بود.');
    }
    if ((int) ($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('حجم عکس باید حداکثر ۴ مگابایت باشد.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => '',
    };
    $info = @getimagesize($tmp);
    if ($ext === '' || $info === false || (int) ($info[0] ?? 0) < 1) {
        throw new RuntimeException('فقط عکس JPG، PNG یا WEBP مجاز است.');
    }
    $dir = $app->root . '/storage/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('پوشه عکس ساخته نشد.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new RuntimeException('ذخیره عکس ناموفق بود.');
    }
    return $name;
}

function catalog_drop_photo(App $app, string $name): void
{
    if (!catalog_photo_name($name)) {
        return;
    }
    $path = $app->root . '/storage/uploads/' . $name;
    if (is_file($path)) {
        unlink($path);
    }
}

function catalog_send_photo(App $app, string $name): void
{
    if (!catalog_photo_name($name)) {
        http_response_code(404);
        exit;
    }
    $path = $app->root . '/storage/uploads/' . $name;
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . $name . '"');
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}

function catalog_frame(string $image, string $line, string $label): string
{
    $inner = catalog_photo_name($image)
        ? '<img src="' . e(url('/catalog/photo/' . $image)) . '" alt="' . e($label) . '">'
        : catalog_art($line);
    return '<div class="shop-frame">' . $inner . '</div>';
}

function catalog_art(string $line): string
{
    $scene = match ($line) {
        'seeding' => '<rect width="400" height="300" fill="#eef2f6"/>'
            . '<path d="M0 214 H400" stroke="#c5d0c0" stroke-width="10"/>'
            . '<rect x="36" y="118" width="328" height="14" rx="4" fill="#17304f"/>'
            . '<path d="M78 118 L68 62 H128 L118 118 Z" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<path d="M168 118 L158 48 H228 L218 118 Z" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<path d="M268 118 L258 62 H318 L308 118 Z" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<path d="M98 132 V196 M198 132 V210 M288 132 V196" stroke="#3e4856" stroke-width="6" stroke-linecap="round"/>'
            . '<circle cx="54" cy="214" r="22" fill="#e2b000" stroke="#172033" stroke-width="4"/>'
            . '<circle cx="346" cy="214" r="22" fill="#e2b000" stroke="#172033" stroke-width="4"/>',
        'care' => '<rect width="400" height="300" fill="#eef2f6"/>'
            . '<path d="M0 228 H400" stroke="#c5d0c0" stroke-width="10"/>'
            . '<rect x="146" y="78" width="108" height="72" rx="28" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<rect x="28" y="104" width="344" height="12" rx="4" fill="#17304f"/>'
            . '<path d="M58 116 V168 M108 116 V168 M196 150 V196 M292 116 V168 M342 116 V168" stroke="#3e4856" stroke-width="6" stroke-linecap="round"/>'
            . '<circle cx="58" cy="176" r="7" fill="#17304f"/><circle cx="108" cy="176" r="7" fill="#17304f"/>'
            . '<circle cx="292" cy="176" r="7" fill="#17304f"/><circle cx="342" cy="176" r="7" fill="#17304f"/>'
            . '<circle cx="92" cy="228" r="22" fill="#e2b000" stroke="#172033" stroke-width="4"/>'
            . '<circle cx="308" cy="228" r="22" fill="#e2b000" stroke="#172033" stroke-width="4"/>',
        'harvest' => '<rect width="400" height="300" fill="#eef2f6"/>'
            . '<path d="M0 232 H400" stroke="#c5d0c0" stroke-width="10"/>'
            . '<path d="M48 168 H250 L286 118 H338 V168 H360 V214 H48 Z" fill="#17304f"/>'
            . '<path d="M286 118 H338 V168 H262 Z" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<rect x="70" y="132" width="54" height="28" rx="4" fill="#eef2f6"/>'
            . '<circle cx="108" cy="228" r="24" fill="#e2b000" stroke="#172033" stroke-width="4"/>'
            . '<circle cx="292" cy="228" r="24" fill="#e2b000" stroke="#172033" stroke-width="4"/>',
        default => '<rect width="400" height="300" fill="#eef2f6"/>'
            . '<path d="M0 220 H400" stroke="#c5d0c0" stroke-width="10"/>'
            . '<path d="M46 150 H214" stroke="#17304f" stroke-width="12" stroke-linecap="round"/>'
            . '<path d="M214 150 L278 86" stroke="#e2b000" stroke-width="12" stroke-linecap="round"/>'
            . '<path d="M96 150 L78 188 H196 L214 150 Z" fill="#3e4856"/>'
            . '<path d="M278 86 L324 124 L278 156 Z" fill="#e2b000" stroke="#172033" stroke-width="3"/>'
            . '<circle cx="72" cy="214" r="24" fill="#e2b000" stroke="#172033" stroke-width="4"/>'
            . '<circle cx="318" cy="196" r="22" fill="#e2b000" stroke="#172033" stroke-width="4"/>',
    };
    return '<svg viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice" aria-hidden="true">' . $scene . '</svg>';
}
