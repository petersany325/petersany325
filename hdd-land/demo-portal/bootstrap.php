<?php

declare(strict_types=1);

const DEMO_PIN = '1234';
const DEMO_SCHEMA_VERSION = '3';

foreach ([
    dirname(__DIR__) . '/public/_demo_boot.php',
    dirname(__DIR__) . '/public/_wipe_boot.php',
] as $leftover) {
    if (is_file($leftover)) {
        @unlink($leftover);
    }
}

session_name('hl_demo');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function demo_base(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim(dirname($script), '/.');
    return $base === '' ? '' : $base;
}

function demo_url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return (demo_base() ?: '') . '/';
    }
    return demo_base() . $path;
}

function demo_path(): string
{
    $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = demo_base();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    return trim($uri, '/');
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . demo_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['_csrf'];
}

function csrf_check(): void
{
    $ok = hash_equals((string) ($_SESSION['_csrf'] ?? ''), (string) ($_POST['_csrf'] ?? ''));
    if (!$ok) {
        http_response_code(419);
        exit('نشست منقضی شد. برگشت بزنید.');
    }
}

function flash_set(string $type, string $text): void
{
    $_SESSION['_flash'] = ['type' => $type, 'text' => $text];
}

function flash_take(): ?array
{
    $row = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($row) ? $row : null;
}

function current_user(): ?array
{
    $user = $_SESSION['demo_user'] ?? null;
    return is_array($user) ? $user : null;
}

function require_user(?array $roles = null): array
{
    $user = current_user();
    if (!$user) {
        redirect('/login');
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        flash_set('err', 'این کارتابل برای نقش شما باز نیست.');
        redirect('/');
    }
    return $user;
}

function demo_sites(): array
{
    return [
        'repair-shop' => [
            'slug' => 'repair-shop',
            'title' => 'سایت مدیریت تعمیرکاران',
            'short' => 'قبض، ارجاع، تأیید هزینه',
            'tagline' => 'مسیر کوتاه قبض: پذیرش → ارجاع → تأیید هزینه → مشتری',
            'kind' => 'repair',
        ],
        'online-store' => [
            'slug' => 'online-store',
            'title' => 'سایت فروشگاهی',
            'short' => 'کاتالوگ و سفارش آزمایشی',
            'tagline' => 'فروشگاه دمو؛ پرداخت واقعی ندارد',
            'kind' => 'store',
        ],
        'corporate' => [
            'slug' => 'corporate',
            'title' => 'سایت شرکتی',
            'short' => 'ویترین و استعلام',
            'tagline' => 'فرم استعلام سازمانی روی بانک دمو',
            'kind' => 'corporate',
        ],
        'booking' => [
            'slug' => 'booking',
            'title' => 'سایت خدماتی / نوبت‌دهی',
            'short' => 'رزرو نوبت آزمایشی',
            'tagline' => 'تقویم نوبت بدون پیامک واقعی',
            'kind' => 'booking',
        ],
    ];
}

function demo_site(string $slug): ?array
{
    return demo_sites()[$slug] ?? null;
}

function demo_roles(): array
{
    return [
        'customer' => [
            'key' => 'customer',
            'letter' => 'م',
            'title' => 'ورود مشتری',
            'lead' => 'کارتابل مشتری — پیگیری قبض، تأیید هزینه و سفارش. پیامک واقعی ارسال نمی‌شود.',
        ],
        'staff' => [
            'key' => 'staff',
            'letter' => 'ک',
            'title' => 'ورود کارمندان',
            'lead' => 'پذیرش، ارجاع، تأیید هزینه، فروش و نوبت. منو با نقش کارمند باز می‌شود.',
        ],
        'trainee' => [
            'key' => 'trainee',
            'letter' => 'آ',
            'title' => 'ورود کارآموز',
            'lead' => 'پرتال کارآموز — دفتر روز و مشاهده محدود قبض‌ها.',
        ],
    ];
}

function status_label(string $status): string
{
    return match ($status) {
        'intake' => 'پذیرش',
        'referred' => 'ارجاع‌شده',
        'with_tech' => 'دست تعمیر',
        'cost_pending' => 'منتظر تأیید هزینه',
        'cost_ok' => 'هزینه تأیید شد',
        'cost_rejected' => 'هزینه رد شد',
        'ready' => 'آماده تحویل',
        'delivered' => 'تحویل شد',
        'open' => 'باز',
        'paid_demo' => 'پرداخت دمو',
        'new' => 'جدید',
        'booked' => 'رزرو شد',
        default => $status,
    };
}

function load_config(): array|string
{
    $local = __DIR__ . '/config.local.php';
    $sample = __DIR__ . '/config.sample.php';
    if (is_file($local)) {
        $cfg = require $local;
        return is_array($cfg) ? $cfg : 'فایل پیکربندی محلی نامعتبر است.';
    }
    if (is_file($sample)) {
        return 'config.local.php روی سرور نیست. این فایل فقط با FTP ساخته می‌شود و داخل گیت نمی‌آید.';
    }
    return 'پیکربندی دمو پیدا نشد.';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $cfg = load_config();
    if (!is_array($cfg)) {
        throw new RuntimeException($cfg);
    }
    $pass = (string) ($cfg['password'] ?? '');
    if ($pass === '') {
        throw new RuntimeException('رمز دیتابیس در config.local.php خالی است.');
    }
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $cfg['host'] ?? 'localhost',
        $cfg['database'] ?? '',
        $cfg['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, (string) ($cfg['username'] ?? ''), $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function ensure_schema(): void
{
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_meta (
            k VARCHAR(64) PRIMARY KEY,
            v TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_actors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(24) NOT NULL,
            name VARCHAR(128) NOT NULL,
            mobile VARCHAR(20) NOT NULL,
            pin VARCHAR(8) NOT NULL,
            title VARCHAR(128) NOT NULL,
            UNIQUE KEY uq_mobile (mobile)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_slug VARCHAR(32) NOT NULL,
            code VARCHAR(32) NOT NULL,
            customer_name VARCHAR(128) NOT NULL,
            customer_mobile VARCHAR(20) NOT NULL,
            device VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL,
            technician VARCHAR(128) DEFAULT '',
            note TEXT,
            labor_amount INT NOT NULL DEFAULT 0,
            customer_decision VARCHAR(16) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_id INT DEFAULT NULL,
            site_slug VARCHAR(32) NOT NULL,
            actor_role VARCHAR(32) NOT NULL,
            action VARCHAR(64) NOT NULL,
            detail TEXT,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_slug VARCHAR(32) NOT NULL,
            name VARCHAR(128) NOT NULL,
            sku VARCHAR(32) NOT NULL,
            price_label VARCHAR(64) NOT NULL,
            stock INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_slug VARCHAR(32) NOT NULL,
            code VARCHAR(32) NOT NULL,
            customer_name VARCHAR(128) NOT NULL,
            items_text TEXT NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_slug VARCHAR(32) NOT NULL,
            name VARCHAR(128) NOT NULL,
            mobile VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demo_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_slug VARCHAR(32) NOT NULL,
            service VARCHAR(128) NOT NULL,
            slot_label VARCHAR(128) NOT NULL,
            customer_name VARCHAR(128) NOT NULL,
            mobile VARCHAR(20) NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ver = $pdo->prepare('SELECT v FROM demo_meta WHERE k = ?');
    $ver->execute(['schema']);
    $current = (string) $ver->fetchColumn();
    if ($current !== DEMO_SCHEMA_VERSION) {
        seed_demo(true);
        $pdo->prepare('REPLACE INTO demo_meta (k, v) VALUES (?, ?)')->execute(['schema', DEMO_SCHEMA_VERSION]);
    }
}

function seed_demo(bool $reset = false): void
{
    $pdo = db();
    if ($reset) {
        foreach (['demo_events', 'demo_receipts', 'demo_orders', 'demo_leads', 'demo_bookings', 'demo_products', 'demo_actors'] as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
    }

    $actors = [
        ['customer', 'مریم رضایی', '09120000001', DEMO_PIN, 'مشتری دمو'],
        ['staff', 'رضا پذیرش', '09120000002', DEMO_PIN, 'پذیرش / حسابدار'],
        ['trainee', 'علی کارآموز', '09120000003', DEMO_PIN, 'کارآموز دوره تعمیر'],
        ['staff', 'حسین تعمیرکار', '09120000004', DEMO_PIN, 'تعمیرکار هارد'],
    ];
    $insA = $pdo->prepare('INSERT INTO demo_actors (role, name, mobile, pin, title) VALUES (?,?,?,?,?)');
    foreach ($actors as $row) {
        $insA->execute($row);
    }

    $now = date('Y-m-d H:i:s');
    $receipts = [
        ['repair-shop', 'HL-1405-101', 'مریم رضایی', '09120000001', 'هارد WD Blue 2TB — کلیک و صدای غیرعادی', 'intake', '', 'پذیرش شد؛ هنوز ارجاع نشده', 0, 'pending'],
        ['repair-shop', 'HL-1405-102', 'کامران نوری', '09120000011', 'SSD NVMe 1TB — سیستم بالا نمی‌آید', 'with_tech', 'حسین تعمیرکار', 'دست تعمیر؛ گزارش اولیه: کنترلر پاسخ می‌دهد', 0, 'pending'],
        ['repair-shop', 'HL-1405-103', 'شرکت آموت', '09120000012', 'سرور RAID5 — یک دیسک آفلاین', 'cost_pending', 'حسین تعمیرکار', 'بازسازی آرایه پیشنهاد شد', 18000000, 'pending'],
        ['repair-shop', 'HL-1405-104', 'سارا محمدی', '09120000013', 'هارد لپ‌تاپ 1TB — آب‌خوردگی', 'cost_ok', 'حسین تعمیرکار', 'مشتری هزینه را تأیید کرد', 9500000, 'approved'],
    ];
    $insR = $pdo->prepare('INSERT INTO demo_receipts (site_slug, code, customer_name, customer_mobile, device, status, technician, note, labor_amount, customer_decision, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($receipts as $row) {
        $insR->execute([...$row, $now, $now]);
    }

    $products = [
        ['online-store', 'هارد اکسترنال ۲ ترابایت', 'WD-2T', 'تماس بگیرید', 6],
        ['online-store', 'SSD M.2 NVMe ۱ ترابایت', 'NV-1T', 'تماس بگیرید', 4],
        ['online-store', 'رم سرور ۳۲ گیگ', 'RAM-32', 'تماس بگیرید', 3],
    ];
    $insP = $pdo->prepare('INSERT INTO demo_products (site_slug, name, sku, price_label, stock) VALUES (?,?,?,?,?)');
    foreach ($products as $row) {
        $insP->execute($row);
    }

    $insO = $pdo->prepare('INSERT INTO demo_orders (site_slug, code, customer_name, items_text, status, created_at) VALUES (?,?,?,?,?,?)');
    $insO->execute(['online-store', 'SO-1405-21', 'مریم رضایی', 'SSD M.2 NVMe ۱ ترابایت × ۱', 'open', $now]);
    $insO->execute(['online-store', 'SO-1405-22', 'شرکت آموت', 'رم سرور ۳۲ گیگ × ۲', 'paid_demo', $now]);

    $insB = $pdo->prepare('INSERT INTO demo_bookings (site_slug, service, slot_label, customer_name, mobile, status, created_at) VALUES (?,?,?,?,?,?,?)');
    $insB->execute(['booking', 'مشاوره بازیابی اطلاعات', 'شنبه ۱۸ مهر — ۱۰:۰۰', 'مریم رضایی', '09120000001', 'booked', $now]);
    $insB->execute(['booking', 'ارزیابی تعمیر هارد', 'یکشنبه ۱۹ مهر — ۱۲:۳۰', 'کامران نوری', '09120000011', 'booked', $now]);

    $insL = $pdo->prepare('INSERT INTO demo_leads (site_slug, name, mobile, message, created_at) VALUES (?,?,?,?,?)');
    $insL->execute(['corporate', 'واحد IT آموت', '01144447220', 'درخواست دمو سایت شرکتی و اتصال به قبض', $now]);
}

function add_event(?int $receiptId, string $site, string $role, string $action, string $detail): void
{
    db()->prepare('INSERT INTO demo_events (receipt_id, site_slug, actor_role, action, detail, created_at) VALUES (?,?,?,?,?,?)')
        ->execute([$receiptId, $site, $role, $action, $detail, date('Y-m-d H:i:s')]);
}

function money_label(int $amount): string
{
    if ($amount <= 0) {
        return 'هنوز اعلام نشده';
    }
    return number_format($amount) . ' تومان (دمو)';
}
