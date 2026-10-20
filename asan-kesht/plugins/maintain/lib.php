<?php

declare(strict_types=1);

function maintain_ident(string $name): string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('نام جدول معتبر نیست.');
    }
    return '`' . $name . '`';
}

/** @return list<array<string, mixed>> */
function maintain_ak_tables(App $app): array
{
    $rows = $app->db->all(
        'SELECT TABLE_NAME AS name, ENGINE AS engine, DATA_LENGTH AS bytes, INDEX_LENGTH AS indexes
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME REGEXP "^ak_"'
    );
    $out = [];
    foreach ($rows as $row) {
        $name = (string) ($row['name'] ?? '');
        if (!preg_match('/^ak_[A-Za-z0-9_]+$/', $name)) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

function maintain_size(int $bytes): string
{
    if ($bytes < 1024) {
        return fa_digits((string) $bytes) . ' بایت';
    }
    if ($bytes < 1048576) {
        return fa_digits((string) (int) round($bytes / 1024)) . ' کیلوبایت';
    }
    return fa_digits(number_format($bytes / 1048576, 1, '.', '')) . ' مگابایت';
}

function maintain_mkdir(string $path): bool
{
    return is_dir($path) || mkdir($path, 0755, true);
}

/** @return list<array{name:string,bytes:int,mtime:int}> */
function maintain_backups(App $app): array
{
    $dir = $app->root . '/storage/backups';
    if (!is_dir($dir)) {
        return [];
    }
    $out = [];
    foreach (scandir($dir) ?: [] as $name) {
        if (!is_string($name) || !preg_match('/^asan-\d{8}-\d{6}-[a-f0-9]{4}\.zip$/', $name)) {
            continue;
        }
        $path = $dir . '/' . $name;
        if (!is_file($path)) {
            continue;
        }
        $out[] = [
            'name' => $name,
            'bytes' => (int) filesize($path),
            'mtime' => (int) filemtime($path),
        ];
    }
    usort($out, static fn (array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
    return $out;
}

function maintain_backup_file(App $app, string $name): string
{
    if (!preg_match('/^asan-\d{8}-\d{6}-[a-f0-9]{4}\.zip$/', $name)) {
        throw new RuntimeException('نام پشتیبان معتبر نیست.');
    }
    $path = $app->root . '/storage/backups/' . $name;
    if (!is_file($path)) {
        throw new RuntimeException('این پشتیبان پیدا نشد.');
    }
    return $path;
}

/** @return array<string, int|string|bool> */
function maintain_status(App $app): array
{
    $tables = maintain_ak_tables($app);
    $bytes = 0;
    foreach ($tables as $table) {
        $bytes += (int) ($table['bytes'] ?? 0) + (int) ($table['indexes'] ?? 0);
    }
    $backups = maintain_backups($app);
    $cacheDir = $app->root . '/storage/cache';
    $cacheFiles = 0;
    if (is_dir($cacheDir)) {
        $cacheFiles = maintain_count_files($cacheDir);
    }
    return [
        'php' => PHP_VERSION,
        'tables' => count($tables),
        'bytes' => $bytes,
        'cache_files' => $cacheFiles,
        'opcache' => function_exists('opcache_get_status') && (bool) @opcache_get_status(false),
        'zip' => class_exists(ZipArchive::class),
        'backups' => count($backups),
        'latest' => $backups[0]['name'] ?? '',
        'storage' => is_writable($app->root . '/storage'),
    ];
}

function maintain_count_files(string $dir): int
{
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }
    return $count;
}

/** @return list<array{ok:bool,text:string}> */
function maintain_clear_cache(App $app): array
{
    $dir = $app->root . '/storage/cache';
    $removed = 0;
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
                continue;
            }
            if (@unlink($file->getPathname())) {
                $removed++;
            }
        }
    } else {
        maintain_mkdir($dir);
    }
    clearstatcache(true);
    $lines = [['ok' => true, 'text' => fa_digits((string) $removed) . ' فایل موقت پاک شد']];
    if (function_exists('opcache_invalidate')) {
        $phpFiles = maintain_invalidate_scripts($app->root);
        $lines[] = ['ok' => true, 'text' => 'کش ' . fa_digits((string) $phpFiles) . ' فایل PHP همین سایت تازه شد'];
    } else {
        $lines[] = ['ok' => true, 'text' => 'کش PHP روی این میزبان جداگانه فعال نیست'];
    }
    return $lines;
}

function maintain_invalidate_scripts(string $root): int
{
    $rootReal = realpath($root);
    if ($rootReal === false || !function_exists('opcache_invalidate')) {
        return 0;
    }
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        $real = realpath($file->getPathname());
        if ($real === false || !str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($real, strlen($rootReal) + 1));
        if (maintain_skip($relative)) {
            continue;
        }
        @opcache_invalidate($real, true);
        $count++;
    }
    return $count;
}

/** @return list<array{ok:bool,text:string}> */
function maintain_repair_site(App $app): array
{
    $lines = [];
    foreach (['storage', 'storage/uploads', 'storage/cache', 'storage/backups', 'storage/logs'] as $dir) {
        $path = $app->root . '/' . $dir;
        if (is_dir($path)) {
            $lines[] = ['ok' => is_writable($path), 'text' => $dir . (is_writable($path) ? ' آماده است' : ' قابل نوشتن نیست')];
            continue;
        }
        $made = maintain_mkdir($path);
        $lines[] = ['ok' => $made, 'text' => $made ? $dir . ' ساخته شد' : $dir . ' ساخته نشد'];
    }
    foreach (['storage/.htaccess', 'storage/uploads/.htaccess', 'storage/cache/.htaccess', 'storage/backups/.htaccess', 'storage/logs/.htaccess'] as $file) {
        $path = $app->root . '/' . $file;
        $current = is_file($path) ? (string) file_get_contents($path) : '';
        if (str_contains($current, 'Require all denied')) {
            $lines[] = ['ok' => true, 'text' => $file . ' از بیرون بسته است'];
            continue;
        }
        $written = file_put_contents($path, "Require all denied\n");
        $lines[] = ['ok' => $written !== false, 'text' => $written !== false ? $file . ' بسته شد' : $file . ' نوشته نشد'];
    }
    foreach (['index.php', '.htaccess', 'config.php', 'app/App.php'] as $file) {
        $ok = is_file($app->root . '/' . $file);
        $lines[] = ['ok' => $ok, 'text' => $ok ? $file . ' سر جایش است' : $file . ' پیدا نشد'];
    }
    foreach (['pdo_mysql' => 'اتصال پایگاه', 'fileinfo' => 'تشخیص فایل', 'zip' => 'فشرده‌سازی', 'mbstring' => 'متن فارسی'] as $ext => $label) {
        $ok = extension_loaded($ext);
        $lines[] = ['ok' => $ok, 'text' => $label . ($ok ? ' فعال است' : ' روی PHP نیست')];
    }
    $lines[] = ['ok' => PHP_VERSION_ID >= 80100, 'text' => 'PHP ' . PHP_VERSION];
    $folders = [];
    foreach (glob($app->root . '/plugins/*/plugin.php') ?: [] as $file) {
        $folders[] = basename(dirname($file));
    }
    $known = [];
    foreach ($app->db->all('SELECT id, enabled FROM ak_plugins') as $row) {
        $known[(string) $row['id']] = (int) $row['enabled'] === 1;
    }
    $missing = 0;
    foreach ($folders as $id) {
        if (!array_key_exists($id, $known)) {
            $missing++;
            $lines[] = ['ok' => false, 'text' => 'افزونه ' . $id . ' در فهرست مدیریت نیست'];
        }
    }
    foreach ($known as $id => $enabled) {
        if ($enabled && !in_array($id, $folders, true)) {
            $missing++;
            $lines[] = ['ok' => false, 'text' => 'پوشه افزونه روشن ' . $id . ' نیست'];
        }
    }
    $migrations = array_map(
        static fn (array $row): string => (string) $row['id'],
        $app->db->all('SELECT id FROM ak_schema_migrations')
    );
    foreach ($folders as $id) {
        if (($known[$id] ?? false) && !in_array($id . ':install', $migrations, true)) {
            $missing++;
            $lines[] = ['ok' => false, 'text' => 'نصب افزونه ' . $id . ' در پایگاه ثبت نشده'];
        }
    }
    if ($missing === 0) {
        $lines[] = ['ok' => true, 'text' => 'افزونه‌های روشن پوشه و نصب دارند'];
    }
    return $lines;
}

/** @return list<array{ok:bool,text:string}> */
function maintain_repair_database(App $app): array
{
    $tables = maintain_ak_tables($app);
    if ($tables === []) {
        return [['ok' => false, 'text' => 'جدول این سایت پیدا نشد']];
    }
    $healthy = 0;
    $lines = [];
    $pdo = $app->db->pdo();
    foreach ($tables as $table) {
        $name = (string) $table['name'];
        $ident = maintain_ident($name);
        try {
            $checks = $pdo->query('CHECK TABLE ' . $ident)->fetchAll();
        } catch (Throwable) {
            $lines[] = ['ok' => false, 'text' => $name . ' بررسی نشد'];
            continue;
        }
        $error = false;
        foreach ($checks as $check) {
            if (!is_array($check)) {
                continue;
            }
            $type = strtolower((string) ($check['Msg_type'] ?? ''));
            $text = strtolower((string) ($check['Msg_text'] ?? ''));
            if ($type === 'error' || str_contains($text, 'corrupt')) {
                $error = true;
            }
        }
        if (!$error) {
            $healthy++;
            continue;
        }
        $engine = strtolower((string) ($table['engine'] ?? ''));
        $sql = in_array($engine, ['myisam', 'archive', 'csv'], true)
            ? 'REPAIR TABLE ' . $ident
            : 'OPTIMIZE TABLE ' . $ident;
        try {
            $repaired = $pdo->query($sql)->fetchAll();
        } catch (Throwable) {
            $lines[] = ['ok' => false, 'text' => $name . ' خراب بود و بازسازی نشد'];
            continue;
        }
        $fixed = true;
        foreach ($repaired as $row) {
            if (is_array($row) && strtolower((string) ($row['Msg_type'] ?? '')) === 'error') {
                $fixed = false;
            }
        }
        $lines[] = ['ok' => $fixed, 'text' => $name . ($fixed ? ' بازسازی شد' : ' بازسازی نشد')];
    }
    array_unshift($lines, ['ok' => true, 'text' => fa_digits((string) $healthy) . ' جدول سالم بود و دست نخورد']);
    $lines[] = ['ok' => true, 'text' => 'فقط جدول‌های پیشوند ak_ بررسی شدند'];
    return $lines;
}

/** @return array{name:string,bytes:int,files:int} */
function maintain_backup(App $app): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('فشرده‌سازی Zip روی PHP نیست.');
    }
    $dir = $app->root . '/storage/backups';
    if (!maintain_mkdir($dir)) {
        throw new RuntimeException('پوشه پشتیبان ساخته نشد.');
    }
    if (count(maintain_backups($app)) >= 8) {
        throw new RuntimeException('هشت پشتیبان ذخیره است. یکی را حذف کنید و دوباره بسازید.');
    }
    foreach (glob($dir . '/*.zip.sql') ?: [] as $stale) {
        if (is_string($stale) && is_file($stale)) {
            @unlink($stale);
        }
    }
    $name = 'asan-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '.zip';
    $zipPath = $dir . '/' . $name;
    $sqlPath = $dir . '/' . $name . '.sql';
    try {
        maintain_write_sql($app, $sqlPath);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('فایل پشتیبان باز نشد.');
        }
        if (!$zip->addFile($sqlPath, 'database.sql')) {
            $zip->close();
            throw new RuntimeException('پایگاه داخل پشتیبان قرار نگرفت.');
        }
        $files = maintain_add_tree($zip, $app->root);
        $zip->close();
    } catch (Throwable $e) {
        @unlink($sqlPath);
        @unlink($zipPath);
        if ($e instanceof RuntimeException) {
            throw $e;
        }
        throw new RuntimeException('ساخت پشتیبان انجام نشد.');
    }
    @unlink($sqlPath);
    return ['name' => $name, 'bytes' => (int) filesize($zipPath), 'files' => $files];
}

function maintain_write_sql(App $app, string $path): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('نوشتن پایگاه ممکن نشد.');
    }
    fwrite($handle, "-- asan-kesht ak_ tables only\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    $pdo = $app->db->pdo();
    foreach (maintain_ak_tables($app) as $table) {
        $name = (string) $table['name'];
        $ident = maintain_ident($name);
        $create = $pdo->query('SHOW CREATE TABLE ' . $ident)->fetch();
        $ddl = is_array($create) ? (string) ($create['Create Table'] ?? '') : '';
        if ($ddl === '') {
            fclose($handle);
            throw new RuntimeException('ساختار یک جدول خوانده نشد.');
        }
        fwrite($handle, "\nDROP TABLE IF EXISTS {$ident};\n{$ddl};\n");
        $stmt = $pdo->query('SELECT * FROM ' . $ident);
        $batch = [];
        $columns = null;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row)) {
                continue;
            }
            if ($columns === null) {
                $columns = array_keys($row);
                foreach ($columns as $column) {
                    if (!is_string($column)) {
                        fclose($handle);
                        throw new RuntimeException('ستون جدول معتبر نیست.');
                    }
                    maintain_ident($column);
                }
            }
            $values = [];
            foreach ($columns as $column) {
                $value = $row[$column];
                if ($value === null) {
                    $values[] = 'NULL';
                    continue;
                }
                $quoted = $pdo->quote((string) $value);
                if ($quoted === false) {
                    fclose($handle);
                    throw new RuntimeException('یک مقدار جدول نقل نشد.');
                }
                $values[] = $quoted;
            }
            $batch[] = '(' . implode(',', $values) . ')';
            if (count($batch) >= 40 && $columns !== null) {
                maintain_flush_insert($handle, $ident, $columns, $batch);
                $batch = [];
            }
        }
        $stmt->closeCursor();
        if ($batch !== [] && $columns !== null) {
            maintain_flush_insert($handle, $ident, $columns, $batch);
        }
    }
    fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);
}

/** @param list<string> $columns @param list<string> $batch */
function maintain_flush_insert($handle, string $ident, array $columns, array $batch): void
{
    $list = implode(',', array_map(static fn (string $column): string => maintain_ident($column), $columns));
    fwrite($handle, 'INSERT INTO ' . $ident . ' (' . $list . ") VALUES\n" . implode(",\n", $batch) . ";\n");
}

function maintain_add_tree(ZipArchive $zip, string $root): int
{
    $rootReal = realpath($root);
    if ($rootReal === false) {
        throw new RuntimeException('ریشه سایت خوانده نشد.');
    }
    $added = 0;
    $total = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $relative = substr($real, strlen($rootReal) + 1);
        $relative = str_replace('\\', '/', $relative);
        if (maintain_skip($relative) || str_ends_with($relative, '.sql')) {
            continue;
        }
        $size = (int) $file->getSize();
        if ($size > 32 * 1024 * 1024) {
            continue;
        }
        $total += $size;
        if ($total > 120 * 1024 * 1024) {
            throw new RuntimeException('حجم سایت برای یک پشتیبان زیاد است.');
        }
        if ($zip->addFile($real, $relative)) {
            $added++;
        }
    }
    return $added;
}

function maintain_skip(string $relative): bool
{
    foreach (['storage/backups', 'storage/cache', 'storage/logs', '.git'] as $prefix) {
        if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
            return true;
        }
    }
    return false;
}

function maintain_send(App $app, string $name): void
{
    $path = maintain_backup_file($app, $name);
    header('Content-Type: application/zip');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}
