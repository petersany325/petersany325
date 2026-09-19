<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

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
    $mobile = preg_replace('/\D+/', '', (string) ($_POST['mobile'] ?? ''));
    $pin = trim((string) ($_POST['pin'] ?? ''));
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
        redirect('/s/' . $site);
    }
    redirect('/');
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

function page_home(): void
{
    $user = current_user();
    $wanted = preg_replace('/[^a-z-]/', '', (string) ($_GET['site'] ?? ''));
    $doors = '';
    foreach (demo_roles() as $role) {
        $href = demo_url('/login/' . $role['key'] . ($wanted !== '' ? '?site=' . rawurlencode($wanted) : ''));
        $doors .= '<a class="door" href="' . h($href) . '"><span class="mark">' . h($role['letter']) . '</span><span><strong>' . h($role['title']) . '</strong><span>' . h($role['lead']) . '</span></span><b>←</b></a>';
    }
    $cards = '';
    foreach (demo_sites() as $site) {
        $href = $user ? demo_url('/s/' . $site['slug']) : demo_url('/login?site=' . rawurlencode($site['slug']));
        $cards .= '<a class="card" href="' . h($href) . '"><em>' . h($site['short']) . '</em><strong>' . h($site['title']) . '</strong><span>' . h($site['tagline']) . '</span></a>';
    }
    $reset = '';
    if ($user && $user['role'] === 'staff') {
        $reset = '<form method="post" action="' . h(demo_url('/reset')) . '"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '"><button class="btn" type="submit">بازنشانی دادهٔ آزمایشی</button></form>';
    }
    $body = '
      <section class="hero">
        <p class="brand">HDD LAND · DEMO</p>
        <h1>دموهای زنده سرزمین هارد</h1>
        <p>چهار محصول فروش سایت روی یک بانک آزمایشی. داده فروشگاه اصلی و سایت قبض <strong>support.hdd-land.ir</strong> اینجا نیست. پین همه نقش‌ها ۱۲۳۴ است و پیامک/پرداخت واقعی خاموش است.</p>
      </section>
      <div class="panel">
        <h2>نوع ورود را انتخاب کنید</h2>
        <p class="muted">همان الگوی سه‌در سایت قبض: مشتری، کارمند، کارآموز.</p>
        <div class="grid">' . $doors . '</div>
      </div>
      <div class="panel">
        <h2>سایت‌های داخل همین دمو</h2>
        <div class="grid grid-2">' . $cards . '</div>
      </div>
      <div class="note">موبایل‌های آماده: مشتری ۰۹۱۲۰۰۰۰۰۰۱ · کارمند ۰۹۱۲۰۰۰۰۰۰۲ · کارآموز ۰۹۱۲۰۰۰۰۰۰۳ · تعمیرکار ۰۹۱۲۰۰۰۰۰۰۴ — پین ۱۲۳۴</div>
      ' . $reset;
    render_layout('دموهای سرزمین هارد', $body);
}

function page_login(string $role, string $site): void
{
    $roles = demo_roles();
    if ($role === '' || !isset($roles[$role])) {
        $list = '';
        foreach ($roles as $row) {
            $q = $site !== '' ? '?site=' . rawurlencode($site) : '';
            $list .= '<a class="door" href="' . h(demo_url('/login/' . $row['key'] . $q)) . '"><span class="mark">' . h($row['letter']) . '</span><span><strong>' . h($row['title']) . '</strong><span>' . h($row['lead']) . '</span></span><b>←</b></a>';
        }
        render_layout('انتخاب ورود', '<section class="hero"><p class="brand">HDD LAND · DEMO</p><h1>نوع ورود را انتخاب کنید</h1><p>مثل سایت قبض؛ بعد از ورود می‌توانید بین چهار دمو جابه‌جا شوید.</p></section><div class="grid" style="margin-top:1rem">' . $list . '</div>');
        return;
    }
    $info = $roles[$role];
    $preset = match ($role) {
        'customer' => '09120000001',
        'staff' => '09120000002',
        'trainee' => '09120000003',
        default => '',
    };
    $siteField = $site !== '' ? '<input type="hidden" name="site" value="' . h($site) . '">' : '';
    $body = '
      <p class="crumbs"><a href="' . h(demo_url('/')) . '">خانه دمو</a> / ورود</p>
      <section class="hero">
        <p class="brand">' . h($info['title']) . '</p>
        <h1>ورود آزمایشی</h1>
        <p>' . h($info['lead']) . '</p>
      </section>
      <div class="panel">
        <form method="post" action="' . h(demo_url('/login/' . $role)) . '">
          <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
          ' . $siteField . '
          <label>موبایل</label>
          <input name="mobile" value="' . h($preset) . '" inputmode="numeric" autocomplete="username">
          <label>پین دمو</label>
          <input name="pin" value="1234" inputmode="numeric" autocomplete="current-password">
          <button class="btn btn-on" type="submit">ورود به دمو</button>
        </form>
        <p class="muted">پیامک تأیید ساخته نمی‌شود. اگر پین را عوض کنید وارد نمی‌شوید — فقط ۱۲۳۴.</p>
      </div>';
    render_layout($info['title'], $body);
}

function page_site(array $site, string $section): void
{
    $user = require_user();
    if ($site['slug'] === 'repair-shop') {
        page_repair($site, $section, $user);
        return;
    }
    if ($site['slug'] === 'online-store') {
        page_store($site, $user);
        return;
    }
    if ($site['slug'] === 'corporate') {
        page_corporate($site, $user);
        return;
    }
    page_booking($site, $user);
}

function repair_nav(string $section): string
{
    $items = [
        '' => 'میز کار',
        'reception' => 'پذیرش',
        'referral' => 'ارجاع',
        'cost' => 'تأیید هزینه',
        'customer' => 'مشتری',
        'trainee' => 'کارآموز',
    ];
    $html = '<nav class="nav-row">';
    foreach ($items as $key => $label) {
        $on = $section === $key ? ' btn-on' : '';
        $href = demo_url('/s/repair-shop' . ($key !== '' ? '/' . $key : ''));
        $html .= '<a class="btn' . $on . '" href="' . h($href) . '">' . h($label) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}

function page_repair(array $site, string $section, array $user): void
{
    $nav = repair_nav($section);
    $head = '<p class="crumbs"><a href="' . h(demo_url('/')) . '">خانه دمو</a> / ' . h($site['title']) . '</p>
      <section class="hero"><p class="brand">' . h($user['title']) . '</p><h1>' . h($site['title']) . '</h1><p>' . h($site['tagline']) . '</p>
      <ul class="flow"><li>پذیرش</li><li>ارجاع</li><li>تأیید هزینه</li><li>مشتری</li></ul></section>' . $nav;

    if ($section === '' || $section === 'desk') {
        $counts = db()->query("SELECT status, COUNT(*) c FROM demo_receipts WHERE site_slug='repair-shop' GROUP BY status")->fetchAll();
        $tiles = '';
        foreach ($counts as $row) {
            $tiles .= '<div class="card"><em>' . h(status_label((string) $row['status'])) . '</em><strong>' . h((string) $row['c']) . ' قبض</strong></div>';
        }
        render_layout($site['title'], $head . '<div class="grid grid-4">' . $tiles . '</div><div class="note">این دمو مسیر کوتاه قبض است؛ انبار و حسابداری کامل اینجا نیامده تا مشتری سریع گردش کار را ببیند.</div>');
        return;
    }

    if ($section === 'reception') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC")->fetchAll();
        $table = receipt_table($rows, false);
        $form = $user['role'] === 'staff'
            ? '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                <label>نام مشتری</label><input name="customer_name" required>
                <label>موبایل</label><input name="customer_mobile" required inputmode="numeric">
                <label>دستگاه / ایراد</label><input name="device" required>
                <button class="btn btn-on" type="submit">ثبت قبض دمو</button></form>'
            : '<p class="muted">ثبت قبض فقط با ورود کارمند است.</p>';
        render_layout('پذیرش', $head . '<div class="grid grid-2"><div class="panel"><h2>قبض جدید</h2>' . $form . '</div><div class="panel"><h2>لیست قبض‌ها</h2>' . $table . '</div></div>');
        return;
    }

    if ($section === 'referral') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND status IN ('intake','referred','with_tech') ORDER BY id DESC")->fetchAll();
        $html = '';
        foreach ($rows as $row) {
            $html .= '<article class="panel"><strong>' . h($row['code']) . '</strong> · ' . h($row['device']) . '<br><span class="tag">' . h(status_label($row['status'])) . '</span>
              <p class="muted">' . h($row['customer_name']) . ' — تعمیرکار: ' . h($row['technician'] ?: 'هنوز نیست') . '</p>';
            if ($user['role'] === 'staff' && $row['status'] === 'intake') {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <input type="hidden" name="action" value="assign">
                  <input name="technician" value="حسین تعمیرکار">
                  <button class="btn btn-on" type="submit">ارجاع به تعمیرکار</button></form>';
            }
            if (in_array($user['role'], ['staff', 'trainee'], true) && $row['status'] === 'referred') {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-teal" type="submit">تأیید دریافت دستگاه</button></form>';
            }
            $html .= '</article>';
        }
        render_layout('ارجاع', $head . ($html !== '' ? $html : '<p class="empty">قبضی برای ارجاع نیست.</p>'));
        return;
    }

    if ($section === 'cost') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND status IN ('with_tech','cost_pending','cost_ok','cost_rejected') ORDER BY id DESC")->fetchAll();
        $html = '';
        foreach ($rows as $row) {
            $html .= '<article class="panel"><strong>' . h($row['code']) . '</strong> · ' . h($row['device']) . '
              <p><span class="tag">' . h(status_label($row['status'])) . '</span> ' . h(money_label((int) $row['labor_amount'])) . '</p>';
            if ($user['role'] === 'staff' && in_array($row['status'], ['with_tech', 'cost_rejected'], true)) {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <label>مبلغ اجرت (تومان دمو)</label>
                  <input name="amount" value="' . h((string) ($row['labor_amount'] ?: 7500000)) . '" inputmode="numeric">
                  <button class="btn btn-on" type="submit">ارسال لینک تأیید به مشتری</button></form>
                  <p class="muted">پیامک واقعی خاموش است؛ فقط رویداد دمو نوشته می‌شود.</p>';
            }
            $html .= '</article>';
        }
        render_layout('تأیید هزینه', $head . ($html !== '' ? $html : '<p class="empty">هنوز دستگاهی دست تعمیر نیست.</p>'));
        return;
    }

    if ($section === 'customer') {
        if ($user['role'] === 'customer') {
            $st = db()->prepare("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND (customer_mobile=? OR customer_name=?) ORDER BY id DESC");
            $st->execute([$user['mobile'], $user['name']]);
            $rows = $st->fetchAll();
        } else {
            $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC")->fetchAll();
        }
        $html = '';
        foreach ($rows as $row) {
            $html .= '<article class="panel"><strong>' . h($row['code']) . '</strong><p>' . h($row['device']) . '</p>
              <p><span class="tag">' . h(status_label($row['status'])) . '</span> ' . h(money_label((int) $row['labor_amount'])) . '</p>
              <p class="muted">' . h($row['note']) . '</p>';
            if ($row['status'] === 'cost_pending') {
                $html .= '<form method="post" class="nav-row"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <button class="btn btn-on" name="decision" value="approved" type="submit">تأیید هزینه</button>
                  <button class="btn" name="decision" value="rejected" type="submit">رد هزینه</button></form>';
            }
            $html .= '</article>';
        }
        render_layout('کارتابل مشتری', $head . ($html !== '' ? $html : '<p class="empty">قبضی برای این مشتری نیست. با موبایل ۰۹۱۲۰۰۰۰۰۰۱ وارد شوید.</p>'));
        return;
    }

    if ($section === 'trainee') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC LIMIT 8")->fetchAll();
        $note = $user['role'] === 'trainee'
            ? '<div class="note">کارآموز هزینه و تحویل را عوض نمی‌کند؛ فقط مشاهده و تأیید دریافت در ارجاع.</div>'
            : '<div class="note">پیش‌نمایش پرتال کارآموز. برای محدودیت واقعی با نقش کارآموز وارد شوید.</div>';
        render_layout('کارآموز', $head . $note . '<div class="panel"><h2>دفتر روز — مشاهده</h2>' . receipt_table($rows, false) . '</div>');
        return;
    }

    http_response_code(404);
    render_layout('یافت نشد', $head . '<p>این بخش در دمو کوتاه نیست.</p>');
}

function page_store(array $site, array $user): void
{
    $products = db()->query("SELECT * FROM demo_products WHERE site_slug='online-store'")->fetchAll();
    $orders = db()->query("SELECT * FROM demo_orders WHERE site_slug='online-store' ORDER BY id DESC")->fetchAll();
    $cards = '';
    foreach ($products as $p) {
        $cards .= '<article class="panel"><strong>' . h($p['name']) . '</strong><p class="muted">' . h($p['sku']) . ' · موجودی ' . (int) $p['stock'] . '</p>
          <p><span class="tag">' . h($p['price_label']) . '</span></p>
          <form method="post" action="' . h(demo_url('/s/online-store/order')) . '">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <input type="hidden" name="sku" value="' . h($p['sku']) . '">
            <button class="btn btn-on" type="submit">ثبت سفارش دمو</button>
          </form></article>';
    }
    $ot = '<table><thead><tr><th>کد</th><th>مشتری</th><th>اقلام</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $ot .= '<tr><td>' . h($o['code']) . '</td><td>' . h($o['customer_name']) . '</td><td>' . h($o['items_text']) . '</td><td><span class="tag">' . h(status_label($o['status'])) . '</span></td></tr>';
    }
    $ot .= '</tbody></table>';
    $body = '<p class="crumbs"><a href="' . h(demo_url('/')) . '">خانه دمو</a> / ' . h($site['title']) . '</p>
      <section class="hero"><p class="brand">' . h($user['name']) . '</p><h1>' . h($site['title']) . '</h1><p>' . h($site['tagline']) . '</p></section>
      <div class="grid grid-3">' . $cards . '</div>
      <div class="panel"><h2>سفارش‌های دمو</h2>' . $ot . '</div>';
    render_layout($site['title'], $body);
}

function page_corporate(array $site, array $user): void
{
    $leads = [];
    if ($user['role'] === 'staff') {
        $leads = db()->query("SELECT * FROM demo_leads WHERE site_slug='corporate' ORDER BY id DESC")->fetchAll();
    }
    $list = '';
    foreach ($leads as $row) {
        $list .= '<p><strong>' . h($row['name']) . '</strong> · ' . h($row['mobile']) . '<br><span class="muted">' . h($row['message']) . '</span></p>';
    }
    $body = '<p class="crumbs"><a href="' . h(demo_url('/')) . '">خانه دمو</a> / ' . h($site['title']) . '</p>
      <section class="hero"><p class="brand">فرازنت آمل</p><h1>' . h($site['title']) . '</h1><p>' . h($site['tagline']) . '</p></section>
      <div class="grid grid-2">
        <div class="panel"><h2>خدمات سازمانی دمو</h2><ul class="muted"><li>ویترین برند و اعتمادسازی</li><li>نمونه کار تعمیر و بازیابی</li><li>فرم استعلام روی بانک دمو</li></ul></div>
        <div class="panel"><h2>استعلام دمو</h2>
          <form method="post" action="' . h(demo_url('/s/corporate/lead')) . '">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <label>نام</label><input name="name" value="' . h($user['name']) . '">
            <label>موبایل</label><input name="mobile" value="' . h($user['mobile']) . '">
            <label>پیام</label><textarea name="message" rows="4">درخواست دمو سایت شرکتی</textarea>
            <button class="btn btn-on" type="submit">ارسال استعلام</button>
          </form>
        </div>
      </div>' . ($list !== '' ? '<div class="panel"><h2>استعلام‌های ثبت‌شده</h2>' . $list . '</div>' : '');
    render_layout($site['title'], $body);
}

function page_booking(array $site, array $user): void
{
    $rows = db()->query("SELECT * FROM demo_bookings WHERE site_slug='booking' ORDER BY id DESC")->fetchAll();
    $table = '<table><thead><tr><th>خدمت</th><th>ساعت</th><th>نام</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $table .= '<tr><td>' . h($row['service']) . '</td><td>' . h($row['slot_label']) . '</td><td>' . h($row['customer_name']) . '</td><td><span class="tag">' . h(status_label($row['status'])) . '</span></td></tr>';
    }
    $table .= '</tbody></table>';
    $body = '<p class="crumbs"><a href="' . h(demo_url('/')) . '">خانه دمو</a> / ' . h($site['title']) . '</p>
      <section class="hero"><p class="brand">نوبت دمو</p><h1>' . h($site['title']) . '</h1><p>' . h($site['tagline']) . '</p></section>
      <div class="grid grid-2">
        <div class="panel"><h2>رزرو نوبت</h2>
          <form method="post" action="' . h(demo_url('/s/booking/book')) . '">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <label>خدمت</label>
            <select name="service">
              <option>مشاوره بازیابی اطلاعات</option>
              <option>ارزیابی تعمیر هارد</option>
              <option>جلسه آموزش تعمیر SSD</option>
            </select>
            <label>ساعت پیشنهادی</label>
            <select name="slot">
              <option>دوشنبه ۲۰ مهر — ۰۹:۳۰</option>
              <option>دوشنبه ۲۰ مهر — ۱۱:۰۰</option>
              <option>سه‌شنبه ۲۱ مهر — ۱۶:۰۰</option>
            </select>
            <button class="btn btn-on" type="submit">رزرو دمو</button>
          </form>
        </div>
        <div class="panel"><h2>نوبت‌های ثبت‌شده</h2>' . $table . '</div>
      </div>';
    render_layout($site['title'], $body);
}

function receipt_table(array $rows, bool $actions): string
{
    if (!$rows) {
        return '<p class="empty">موردی نیست.</p>';
    }
    $html = '<table><thead><tr><th>کد</th><th>مشتری</th><th>دستگاه</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr><td>' . h($row['code']) . '</td><td>' . h($row['customer_name']) . '</td><td>' . h($row['device']) . '</td><td><span class="tag">' . h(status_label($row['status'])) . '</span></td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function render_setup(string $error): void
{
    http_response_code(503);
    $body = '<section class="hero"><p class="brand">HDD LAND · DEMO</p><h1>دمو هنوز به بانک وصل نیست</h1>
      <p>ساب‌دامین آماده است؛ باید <code>config.local.php</code> فقط روی سرور (FTP) باشد. رمز دیتابیس داخل گیت نمی‌آید.</p></section>
      <div class="panel"><p class="err">' . h($error) . '</p>
      <p class="muted">بعد از آپلود پیکربندی، همین صفحه جدول‌ها و دادهٔ آزمایشی را می‌سازد.</p></div>';
    render_layout('راه‌اندازی دمو', $body);
}

function render_layout(string $title, string $body): void
{
    $user = current_user();
    $flash = flash_take();
    $flashHtml = '';
    if ($flash) {
        $cls = ($flash['type'] ?? '') === 'err' ? 'flash-err' : 'flash-ok';
        $flashHtml = '<div class="flash ' . $cls . '">' . h((string) $flash['text']) . '</div>';
    }
    $who = $user
        ? '<span class="userchip">' . h($user['name']) . ' · ' . h($user['title']) . ' <a href="' . h(demo_url('/logout')) . '">خروج</a></span>'
        : '<a href="' . h(demo_url('/login')) . '">ورود دمو</a>';
    $css = demo_url('/assets/demo.css') . '?v=3';
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
      <meta name="robots" content="noindex,nofollow">
      <title>' . h($title) . ' | دمو سرزمین هارد</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;800;900&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="' . h($css) . '"></head><body>
      <div class="demo-banner">DEMO — داده آزمایشی · بدون پیامک و پرداخت واقعی · جدا از فروشگاه و سایت قبض</div>
      <div class="topbar"><a href="' . h(demo_url('/')) . '">دموهای HDD LAND</a>' . $who . '</div>
      <main class="wrap">' . $flashHtml . $body . '</main>
      <div class="stamp">DEMO</div>
      </body></html>';
}
