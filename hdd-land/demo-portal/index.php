<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/views.php';

$cfg = load_config();
$dbReady = false;
$dbError = '';
if (is_array($cfg)) {
    try {
        ensure_schema();
        $dbReady = true;
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = $cfg;
}

$path = demo_path();
$parts = $path === '' ? [] : explode('/', $path);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST') {
    csrf_check();
}

try {
    route_demo($parts, $method, $dbReady, $dbError);
} catch (Throwable $e) {
    http_response_code(500);
    render_layout('خطا', '<div class="panel"><h2>خطای دمو</h2><p class="err">' . h($e->getMessage()) . '</p></div>');
}

function route_demo(array $parts, string $method, bool $dbReady, string $dbError): void
{
    $head = $parts[0] ?? '';

    if (!$dbReady && $head !== 'health') {
        render_setup($dbError);
        return;
    }

    if ($head === '' || $head === 'index.php') {
        page_home();
        return;
    }
    if ($head === 'health') {
        header('Content-Type: text/plain; charset=utf-8');
        echo $dbReady ? "ok\n" : "db-error\n";
        return;
    }
    if ($head === 'login') {
        $role = $parts[1] ?? '';
        $site = preg_replace('/[^a-z-]/', '', (string) ($_GET['site'] ?? $_POST['site'] ?? '')) ?: '';
        if ($method === 'POST') {
            handle_login($role, $site);
        }
        page_login($role, $site);
        return;
    }
    if ($head === 'logout') {
        unset($_SESSION['demo_user']);
        flash_set('ok', 'از دمو خارج شدید.');
        redirect('/');
    }
    if ($head === 'reset' && $method === 'POST') {
        require_user(['staff']);
        seed_demo(true);
        db()->prepare('REPLACE INTO demo_meta (k, v) VALUES (?, ?)')->execute(['schema', DEMO_SCHEMA_VERSION]);
        flash_set('ok', 'دادهٔ آزمایشی از نو ساخته شد. قبض‌ها و سفارش‌های دمو برگشتند.');
        redirect('/');
    }
    if ($head === 's') {
        $slug = $parts[1] ?? '';
        $site = demo_site($slug);
        if (!$site) {
            http_response_code(404);
            render_layout('یافت نشد', '<div class="panel"><p>این دمو وجود ندارد.</p></div>');
            return;
        }
        $section = $parts[2] ?? '';
        if ($method === 'POST') {
            handle_site_post($site, $section);
        }
        page_site($site, $section);
        return;
    }

    http_response_code(404);
    render_layout('یافت نشد', '<div class="panel"><p>صفحه پیدا نشد.</p><p><a class="btn" href="' . h(demo_url('/')) . '">خانه دمو</a></p></div>');
}

function handle_login(string $role, string $site): void
{
    $roles = demo_roles();
    if (!isset($roles[$role])) {
        flash_set('err', 'نوع ورود درست نیست.');
        redirect('/login');
    }
    $mobile = preg_replace('/\D+/', '', (string) ($_POST['mobile'] ?? $_POST['login'] ?? $_POST['phone'] ?? ''));
    $pin = trim((string) ($_POST['pin'] ?? $_POST['password'] ?? ''));
    if ($pin === '' && (string) ($_POST['otp_demo'] ?? '') === '1') {
        $pin = DEMO_PIN;
    }
    $st = db()->prepare('SELECT * FROM demo_actors WHERE mobile = ? AND pin = ? LIMIT 1');
    $st->execute([$mobile, $pin]);
    $actor = $st->fetch();
    if (!$actor) {
        flash_set('err', 'موبایل یا پین دمو درست نیست. پین همه نقش‌ها ۱۲۳۴ است.');
        redirect('/login/' . $role . ($site !== '' ? '?site=' . rawurlencode($site) : ''));
    }
    if ($actor['role'] !== $role) {
        flash_set('err', 'این شماره برای «' . $roles[$role]['title'] . '» ثبت نشده. از درِ درست وارد شوید.');
        redirect('/login/' . $role . ($site !== '' ? '?site=' . rawurlencode($site) : ''));
    }
    $_SESSION['demo_user'] = [
        'id' => (int) $actor['id'],
        'role' => $actor['role'],
        'name' => $actor['name'],
        'mobile' => $actor['mobile'],
        'title' => $actor['title'],
    ];
    flash_set('ok', 'وارد شدید — ' . $actor['name'] . ' (' . $actor['title'] . ')');
    if ($site !== '' && demo_site($site)) {
        if ($site === 'repair-shop' && $actor['role'] === 'customer') {
            redirect('/s/repair-shop/customer');
        }
        if ($site === 'repair-shop' && $actor['role'] === 'trainee') {
            redirect('/s/repair-shop/trainee');
        }
        redirect('/s/' . $site);
    }
    if ($actor['role'] === 'customer') {
        redirect('/s/repair-shop/customer');
    }
    if ($actor['role'] === 'trainee') {
        redirect('/s/repair-shop/trainee');
    }
    redirect('/s/repair-shop');
}

function handle_site_post(array $site, string $section): void
{
    $user = require_user();
    $slug = $site['slug'];

    if ($slug === 'repair-shop' && $section === 'reception') {
        if ($user['role'] !== 'staff') {
            flash_set('err', 'فقط کارمند پذیرش می‌تواند قبض بسازد.');
            redirect('/s/repair-shop/reception');
        }
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $mobile = preg_replace('/\D+/', '', (string) ($_POST['customer_mobile'] ?? ''));
        $device = trim((string) ($_POST['device'] ?? ''));
        if ($name === '' || $mobile === '' || $device === '') {
            flash_set('err', 'نام، موبایل و دستگاه لازم است.');
            redirect('/s/repair-shop/reception');
        }
        $code = 'HL-1405-' . (100 + (int) db()->query('SELECT COUNT(*) FROM demo_receipts')->fetchColumn() + 1);
        db()->prepare('INSERT INTO demo_receipts (site_slug, code, customer_name, customer_mobile, device, status, technician, note, labor_amount, customer_decision, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$slug, $code, $name, $mobile, $device, 'intake', '', 'پذیرش دمو', 0, 'pending', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        $id = (int) db()->lastInsertId();
        add_event($id, $slug, $user['role'], 'intake', 'قبض ' . $code . ' ساخته شد. پیامک واقعی ارسال نشد.');
        flash_set('ok', 'قبض ' . $code . ' ثبت شد. پیامک دمو فقط در رویدادها نوشته شد.');
        redirect('/s/repair-shop/reception');
    }

    if ($slug === 'repair-shop' && $section === 'referral') {
        if (!in_array($user['role'], ['staff', 'trainee'], true)) {
            flash_set('err', 'ارجاع برای کارمند یا کارآموز است.');
            redirect('/s/repair-shop/referral');
        }
        $id = (int) ($_POST['receipt_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $receipt = load_receipt($id);
        if (!$receipt) {
            flash_set('err', 'قبض پیدا نشد.');
            redirect('/s/repair-shop/referral');
        }
        if ($action === 'assign' && $user['role'] === 'staff') {
            $tech = trim((string) ($_POST['technician'] ?? 'حسین تعمیرکار'));
            db()->prepare('UPDATE demo_receipts SET status=?, technician=?, updated_at=? WHERE id=?')
                ->execute(['referred', $tech, date('Y-m-d H:i:s'), $id]);
            add_event($id, $slug, $user['role'], 'refer', 'ارجاع به ' . $tech);
            flash_set('ok', 'قبض ارجاع شد.');
        } elseif ($action === 'accept') {
            db()->prepare('UPDATE demo_receipts SET status=?, updated_at=? WHERE id=?')
                ->execute(['with_tech', date('Y-m-d H:i:s'), $id]);
            add_event($id, $slug, $user['role'], 'accept', 'تأیید دریافت — دستگاه دست تعمیر');
            flash_set('ok', 'دریافت تأیید شد. دستگاه دست تعمیر است.');
        } else {
            flash_set('err', 'این اقدام برای نقش شما مجاز نیست.');
        }
        redirect('/s/repair-shop/referral');
    }

    if ($slug === 'repair-shop' && $section === 'cost') {
        if ($user['role'] !== 'staff') {
            flash_set('err', 'اعلام هزینه فقط برای کارمند است.');
            redirect('/s/repair-shop/cost');
        }
        $id = (int) ($_POST['receipt_id'] ?? 0);
        $amount = (int) preg_replace('/\D+/', '', (string) ($_POST['amount'] ?? '0'));
        $receipt = load_receipt($id);
        if (!$receipt) {
            flash_set('err', 'قبض پیدا نشد.');
            redirect('/s/repair-shop/cost');
        }
        db()->prepare('UPDATE demo_receipts SET labor_amount=?, status=?, customer_decision=?, updated_at=? WHERE id=?')
            ->execute([$amount, 'cost_pending', 'pending', date('Y-m-d H:i:s'), $id]);
        add_event($id, $slug, $user['role'], 'cost_sms', 'لینک تأیید هزینه ارسال شد (دمو، پیامک واقعی نیست) — مبلغ ' . money_label($amount));
        flash_set('ok', 'لینک تأیید برای مشتری شبیه‌سازی شد. پیامک واقعی نرفت.');
        redirect('/s/repair-shop/cost');
    }

    if ($slug === 'repair-shop' && $section === 'customer') {
        $id = (int) ($_POST['receipt_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $receipt = load_receipt($id);
        if (!$receipt) {
            flash_set('err', 'قبض پیدا نشد.');
            redirect('/s/repair-shop/customer');
        }
        if ($user['role'] === 'customer' && $receipt['customer_mobile'] !== $user['mobile'] && $receipt['customer_name'] !== $user['name']) {
            flash_set('err', 'این قبض مال کارتابل شما نیست.');
            redirect('/s/repair-shop/customer');
        }
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            flash_set('err', 'تصمیم نامعتبر است.');
            redirect('/s/repair-shop/customer');
        }
        $status = $decision === 'approved' ? 'cost_ok' : 'cost_rejected';
        db()->prepare('UPDATE demo_receipts SET customer_decision=?, status=?, updated_at=? WHERE id=?')
            ->execute([$decision, $status, date('Y-m-d H:i:s'), $id]);
        add_event($id, $slug, $user['role'], 'cost_decision', $decision === 'approved' ? 'مشتری هزینه را تأیید کرد' : 'مشتری هزینه را رد کرد');
        flash_set('ok', $decision === 'approved' ? 'هزینه تأیید شد.' : 'هزینه رد شد.');
        redirect('/s/repair-shop/customer');
    }

    if ($slug === 'online-store' && $section === 'order') {
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $st = db()->prepare('SELECT * FROM demo_products WHERE sku=? AND site_slug=? LIMIT 1');
        $st->execute([$sku, $slug]);
        $product = $st->fetch();
        if (!$product) {
            flash_set('err', 'کالا پیدا نشد.');
            redirect('/s/online-store');
        }
        $code = 'SO-1405-' . (20 + (int) db()->query('SELECT COUNT(*) FROM demo_orders')->fetchColumn() + 1);
        db()->prepare('INSERT INTO demo_orders (site_slug, code, customer_name, items_text, status, created_at) VALUES (?,?,?,?,?,?)')
            ->execute([$slug, $code, $user['name'], $product['name'] . ' × ۱', 'paid_demo', date('Y-m-d H:i:s')]);
        add_event(null, $slug, $user['role'], 'order', 'سفارش ' . $code . ' — پرداخت دمو، درگاه واقعی نیست');
        flash_set('ok', 'سفارش ' . $code . ' ثبت شد. پرداخت واقعی انجام نشد.');
        redirect('/s/online-store');
    }

    if ($slug === 'corporate' && $section === 'lead') {
        $name = trim((string) ($_POST['name'] ?? $user['name']));
        $mobile = preg_replace('/\D+/', '', (string) ($_POST['mobile'] ?? $user['mobile']));
        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '') {
            flash_set('err', 'متن استعلام خالی است.');
            redirect('/s/corporate');
        }
        db()->prepare('INSERT INTO demo_leads (site_slug, name, mobile, message, created_at) VALUES (?,?,?,?,?)')
            ->execute([$slug, $name, $mobile, $message, date('Y-m-d H:i:s')]);
        add_event(null, $slug, $user['role'], 'lead', 'استعلام دمو ثبت شد');
        flash_set('ok', 'استعلام روی بانک دمو نشست. ایمیل/پیامک واقعی نرفت.');
        redirect('/s/corporate');
    }

    if ($slug === 'booking' && $section === 'book') {
        $service = trim((string) ($_POST['service'] ?? ''));
        $slot = trim((string) ($_POST['slot'] ?? ''));
        if ($service === '' || $slot === '') {
            flash_set('err', 'خدمت و ساعت را انتخاب کنید.');
            redirect('/s/booking');
        }
        db()->prepare('INSERT INTO demo_bookings (site_slug, service, slot_label, customer_name, mobile, status, created_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$slug, $service, $slot, $user['name'], $user['mobile'], 'booked', date('Y-m-d H:i:s')]);
        add_event(null, $slug, $user['role'], 'book', $service . ' / ' . $slot . ' — یادآوری پیامکی دمو');
        flash_set('ok', 'نوبت رزرو شد. پیامک یادآوری واقعی ارسال نشد.');
        redirect('/s/booking');
    }

    flash_set('err', 'اقدام ناشناخته.');
    redirect('/s/' . $slug);
}

function load_receipt(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM demo_receipts WHERE id=? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

