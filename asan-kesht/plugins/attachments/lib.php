<?php

declare(strict_types=1);

function attachment_column(Database $db, string $table): void
{
    if (!in_array($table, ['ak_circulars', 'ak_ticket_messages', 'ak_dealer_requests'], true)) {
        return;
    }
    $exists = $db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        [$table]
    );
    if (!$exists) {
        return;
    }
    $row = $db->one(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, 'file']
    );
    if ($row) {
        return;
    }
    $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN `file` VARCHAR(80) NOT NULL DEFAULT ""');
}

function attachment_incoming(string $field): bool
{
    $file = $_FILES[$field] ?? null;
    return is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function attachment_store(App $app, string $field): string
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('بارگذاری پیوست ناموفق بود.');
    }
    if ((int) ($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('حجم پیوست باید حداکثر ۴ مگابایت باشد.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        default => '',
    };
    if ($ext === '') {
        throw new RuntimeException('فقط PDF، JPG، PNG، WEBP یا TXT مجاز است.');
    }
    $dir = $app->root . '/storage/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('پوشه پیوست ساخته نشد.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('ذخیره پیوست ناموفق بود.');
    }
    return $name;
}

function attachment_name_ok(string $name): bool
{
    return preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf|txt)$/', $name) === 1;
}

function attachment_send(App $app, string $name): void
{
    if (!attachment_name_ok($name) || !attachment_may_read($app, $name)) {
        http_response_code(404);
        exit;
    }
    $path = $app->root . '/storage/uploads/' . $name;
    if (!is_file($path)) {
        http_response_code(404);
        exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!is_string($mime) || $mime === '') {
        $mime = 'application/octet-stream';
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    readfile($path);
    exit;
}

function attachment_may_read(App $app, string $name): bool
{
    $user = $app->auth->user();
    if (!is_array($user) || !attachment_name_ok($name) || !attachment_is_referenced($app, $name)) {
        return false;
    }
    $role = (string) ($user['role'] ?? '');
    if (in_array($role, ['admin', 'employee', 'accountant', 'region_manager'], true)) {
        return true;
    }
    $userId = (int) $user['id'];
    if ($role === 'representative') {
        return attachment_hit($app, 'SELECT c.id FROM ak_circulars c JOIN ak_circular_receipts r ON r.circular_id = c.id JOIN ak_dealers d ON d.id = r.dealer_id WHERE c.file = ? AND d.user_id = ? LIMIT 1', [$name, $userId])
            || attachment_hit($app, 'SELECT q.id FROM ak_dealer_requests q JOIN ak_dealers d ON d.id = q.dealer_id WHERE q.file = ? AND d.user_id = ? LIMIT 1', [$name, $userId])
            || attachment_hit($app, 'SELECT m.id FROM ak_ticket_messages m JOIN ak_tickets t ON t.id = m.ticket_id WHERE m.file = ? AND t.user_id = ? LIMIT 1', [$name, $userId]);
    }
    if ($role === 'customer') {
        return attachment_hit($app, 'SELECT m.id FROM ak_ticket_messages m JOIN ak_tickets t ON t.id = m.ticket_id WHERE m.file = ? AND t.user_id = ? LIMIT 1', [$name, $userId]);
    }
    return false;
}

function attachment_is_referenced(App $app, string $name): bool
{
    return attachment_hit($app, 'SELECT id FROM ak_circulars WHERE file = ? LIMIT 1', [$name])
        || attachment_hit($app, 'SELECT id FROM ak_ticket_messages WHERE file = ? LIMIT 1', [$name])
        || attachment_hit($app, 'SELECT id FROM ak_dealer_requests WHERE file = ? LIMIT 1', [$name])
        || attachment_hit($app, 'SELECT id FROM ak_dealership_forms WHERE file = ? LIMIT 1', [$name]);
}

/** @param list<int|string> $params */
function attachment_hit(App $app, string $sql, array $params): bool
{
    try {
        return $app->db->one($sql, $params) !== null;
    } catch (Throwable) {
        return false;
    }
}
