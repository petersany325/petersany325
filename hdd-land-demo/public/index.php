<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$p = path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------- Auth actions ----------
if ($p === '/logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/');
}

if ($p === '/login/staff' && $method === 'POST') {
    csrf_check();
    $login = trim((string) ($_POST['login'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $row = $store->one('SELECT * FROM users WHERE (phone=? OR email=?) AND is_active=1', [$login, $login]);
    if ($row && $pass === (string) $row['password']) {
        $_SESSION['user'] = [
            'kind' => 'staff',
            'id' => $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'role' => $row['role'],
            'role_label' => $row['role_label'],
        ];
        flash('وارد شدید — ' . $row['name'] . ' (' . $row['role_label'] . ')');
        redirect('/s/desk');
    }
    flash('نام کاربری یا رمز اشتباه است. در دمو رمز همه نقش‌ها ۱۲۳۴ است.');
    redirect('/login/staff');
}

if ($p === '/login/customer' && $method === 'POST') {
    csrf_check();
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $otp = trim((string) ($_POST['otp'] ?? cfg('demo_otp')));
    $cust = $store->one('SELECT * FROM customers WHERE phone=?', [$phone]);
    if ($cust && $otp === (string) cfg('demo_otp')) {
        $_SESSION['user'] = [
            'kind' => 'customer',
            'id' => $cust['id'],
            'name' => $cust['name'],
            'phone' => $cust['phone'],
        ];
        flash('ورود مشتری دمو موفق بود.');
        redirect('/c/home');
    }
    // first step: just phone -> show otp form via flash
    if ($cust && empty($_POST['otp'])) {
        $_SESSION['pending_customer_phone'] = $phone;
        flash('کد دمو: ۱۲۳۴');
        redirect('/login/customer?otp=1');
    }
    flash('شماره در دمو یافت نشد. نمونه: ۰۹۱۲۰۰۰۰۰۰۱');
    redirect('/login/customer');
}

if ($p === '/login/trainee' && $method === 'POST') {
    csrf_check();
    $login = trim((string) ($_POST['login'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $row = $store->one('SELECT * FROM users WHERE phone=? AND role=? AND is_active=1', [$login, 'intern']);
    if ($row && $pass === (string) $row['password']) {
        $_SESSION['user'] = [
            'kind' => 'staff',
            'id' => $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'role' => $row['role'],
            'role_label' => $row['role_label'],
        ];
        flash('ورود کارآموز دمو');
        redirect('/s/intern-portal');
    }
    flash('کارآموز دمو: ۰۹۱۲۰۰۰۰۰۸ / ۱۲۳۴');
    redirect('/login/trainee');
}

// ---------- Staff mutations ----------
if (str_starts_with($p, '/s/') && $method === 'POST') {
    $u = require_staff();
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($p === '/s/reception' && $action === 'create') {
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $mobile = trim((string) ($_POST['customer_mobile'] ?? ''));
        $device = trim((string) ($_POST['device'] ?? ''));
        if ($name && $mobile && $device) {
            $cust = $store->one('SELECT * FROM customers WHERE phone=?', [$mobile]);
            if (! $cust) {
                $store->exec('INSERT INTO customers(name,phone,address) VALUES(?,?,?)', [$name, $mobile, '']);
                $cust = $store->one('SELECT * FROM customers WHERE phone=?', [$mobile]);
            }
            $code = $store->nextTicketCode();
            $store->exec(
                'INSERT INTO tickets(code,customer_id,device,status,technician,amount,note,created_at) VALUES(?,?,?,?,?,?,?,?)',
                [$code, $cust['id'], $device, 'پذیرش', null, 0, '', date('Y-m-d H:i:s')]
            );
            $store->exec('INSERT INTO events(ticket_code,body,created_at) VALUES(?,?,?)', [$code, 'قبض ثبت شد. پیامک دمو فقط در رویدادها نوشته شد.', date('Y-m-d H:i:s')]);
            flash('قبض ' . $code . ' ثبت شد. پیامک دمو فقط در رویدادها نوشته شد.');
        } else {
            flash('نام، موبایل و دستگاه الزامی است.');
        }
        redirect('/s/reception');
    }

    if ($p === '/s/referral' && $action === 'assign') {
        $code = (string) ($_POST['code'] ?? '');
        $store->exec('UPDATE tickets SET status=?, technician=? WHERE code=?', ['دست تعمیر', 'علی تعمیرکار', $code]);
        $store->exec('INSERT INTO events(ticket_code,body,created_at) VALUES(?,?,?)', [$code, 'ارجاع به تعمیرکار (دمو)', date('Y-m-d H:i:s')]);
        flash('ارجاع ' . $code . ' انجام شد.');
        redirect('/s/referral');
    }

    if ($p === '/s/referral' && $action === 'accept') {
        $code = (string) ($_POST['code'] ?? '');
        $store->exec('UPDATE tickets SET status=? WHERE code=?', ['دست تعمیر', $code]);
        flash('دریافت ' . $code . ' تأیید شد.');
        redirect('/s/referral');
    }

    if ($p === '/s/cost' && $action === 'send') {
        $code = (string) ($_POST['code'] ?? '');
        $amount = (int) ($_POST['amount'] ?? 0);
        $store->exec('UPDATE tickets SET status=?, amount=? WHERE code=?', ['منتظر تأیید هزینه', $amount, $code]);
        $store->exec('INSERT INTO events(ticket_code,body,created_at) VALUES(?,?,?)', [$code, 'لینک تأیید هزینه ارسال شد (دمو)', date('Y-m-d H:i:s')]);
        flash('لینک تأیید هزینه برای ' . $code . ' ارسال شد (دمو).');
        redirect('/s/cost');
    }

    if ($p === '/s/cost' && $action === 'approve') {
        $code = (string) ($_POST['code'] ?? '');
        $store->exec('UPDATE tickets SET status=? WHERE code=?', ['هزینه تأیید شد', $code]);
        flash('هزینه ' . $code . ' تأیید شد (دمو مشتری).');
        redirect('/s/cost');
    }

    if ($p === '/s/daily-logs' && $action === 'add') {
        $service = trim((string) ($_POST['service'] ?? 'ثبت دستی'));
        $body = trim((string) ($_POST['body'] ?? ''));
        $store->exec('INSERT INTO daily_logs(user_name,service,body,created_at) VALUES(?,?,?,?)', [$u['name'], $service, $body, date('Y-m-d H:i:s')]);
        flash('در دفتر روز ثبت شد.');
        redirect('/s/daily-logs');
    }

    if ($p === '/s/parts' && $action === 'adjust') {
        $id = (int) ($_POST['id'] ?? 0);
        $delta = (int) ($_POST['delta'] ?? 0);
        $store->exec('UPDATE parts SET stock = stock + ? WHERE id=?', [$delta, $id]);
        flash('موجودی به‌روز شد (دمو).');
        redirect('/s/parts');
    }

    if ($p === '/s/tools' && $action === 'reset') {
        $store->resetDemo();
        flash('دیتابیس دمو ریست شد — قبض‌ها و کاربران پیش‌فرض برگشت.');
        redirect('/s/tools');
    }

    if ($p === '/s/delivery' && $action === 'deliver') {
        $code = (string) ($_POST['code'] ?? '');
        $store->exec('UPDATE tickets SET status=? WHERE code=?', ['تحویل‌شده', $code]);
        flash('خروج/تحویل ' . $code . ' ثبت شد (دمو).');
        redirect('/s/delivery');
    }

    flash('اقدام دمو انجام شد.');
    redirect($p);
}

// ---------- Pages ----------
if ($p === '/' || $p === '') {
    render_gate();
    exit;
}

if ($p === '/login/staff') {
    render_login_staff();
    exit;
}
if ($p === '/login/customer') {
    render_login_customer();
    exit;
}
if ($p === '/login/trainee') {
    render_login_trainee();
    exit;
}
if ($p === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

if (str_starts_with($p, '/c/')) {
    $cu = require_customer();
    render_customer($p, $cu);
    exit;
}

if (str_starts_with($p, '/s/')) {
    $u = require_staff();
    render_staff($p, $u);
    exit;
}

http_response_code(404);
render_staff_shell(user() ?? ['name' => '', 'role_label' => ''], 'یافت نشد', '<div class="panel"><p class="lead">صفحه پیدا نشد.</p><a class="btn btn-primary" href="/">بازگشت</a></div>');

// ===================== RENDER HELPERS =====================

function head(string $title, string $skin = 'skin-staff'): void
{
    $logo = e(cfg('logo'));
    $fav = e(cfg('favicon'));
    $banner = e(cfg('demo_banner'));
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">';
    echo '<meta name="theme-color" content="#2b3340"><meta name="robots" content="noindex,nofollow">';
    echo '<title>' . e($title) . '</title>';
    echo '<link rel="icon" href="' . $fav . '">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/assets/demo.css?v=20">';
    echo '<link rel="stylesheet" href="/assets/demo-extra.css?v=20"></head>';
    echo '<body class="' . e($skin) . '"><div class="demo-ribbon">' . $banner . '</div>';
}

function render_gate(): void
{
    head('سرزمین هارد | انتخاب ورود', 'skin-gate');
    $logo = e(cfg('logo'));
    echo '<div class="gate-wrap"><div class="gate"><div class="gate-brand">';
    echo '<div class="gate-logo"><img src="' . $logo . '" alt="سرزمین هارد"></div>';
    echo '<h1>سرزمین هارد</h1><p>سیستم مدیریت تعمیرات — نوع ورود را انتخاب کنید</p></div>';
    echo '<div class="gate-cards">';
    echo card_gate('/login/customer', 'م', 'ورود مشتری', 'وب‌سرویس اختصاصی کارتابل مشتری — موبایل و تأیید پیامک، پیگیری قبض و پرداخت', 'customer');
    echo card_gate('/login/staff', 'ک', 'ورود کارمندان', 'وب‌سرویس موبایل و پنل کامپیوتر — همه منوها برای تست مشتری فعال است', 'staff');
    echo card_gate('/login/trainee', 'آ', 'ورود کارآموز', 'پرتال کارآموز — دفتر روز و خدمات تعریف‌شده شرکت', 'intern');
    echo '</div><div class="gate-foot">demo.hdd-land.ir · نمونه زنده همان سیستم support.hdd-land.ir</div>';
    echo '<div class="gate-demo-creds"><p><strong>راهنمای ورود دمو</strong></p>';
    echo '<ul><li>پذیرش: ۰۹۱۲۰۰۰۰۰۲ / ۱۲۳۴</li><li>تعمیرکار: ۰۹۱۲۰۰۰۰۰۴ / ۱۲۳۴</li><li>مدیر: ۰۹۱۲۰۰۰۰۰۰۰ / ۱۲۳۴</li><li>حسابدار: ۰۹۱۲۰۰۰۰۰۷ / ۱۲۳۴</li><li>کارآموز: ۰۹۱۲۰۰۰۰۰۸ / ۱۲۳۴</li><li>مشتری: ۰۹۱۲۰۰۰۰۰۰۱ — کد ۱۲۳۴</li></ul></div>';
    echo '</div></div></body></html>';
}

function card_gate(string $href, string $ico, string $title, string $desc, string $cls): string
{
    return '<a class="gate-card ' . e($cls) . '" href="' . e($href) . '"><span class="gate-ico">' . e($ico) . '</span><span class="gate-text"><strong>' . e($title) . '</strong><span>' . e($desc) . '</span></span><span class="gate-go">←</span></a>';
}

function render_login_staff(): void
{
    head('ورود | سرزمین هارد', 'skin-login');
    $logo = e(cfg('logo'));
    $msg = flash();
    echo '<div class="login-page"><div class="panel login-card"><div class="brand-center">';
    echo '<img class="brand-logo-lg" src="' . $logo . '" alt="" width="96" height="96"><h1>سرزمین هارد</h1><p>ورود کارمند — همه منوها فعال (دمو)</p></div>';
    echo '<p class="hint" style="text-align:center;margin:0 0 12px;"><a href="/" style="font-size:12px;font-weight:700;color:#0f766e;">→ بازگشت به انتخاب ورود</a></p>';
    if ($msg) {
        echo '<div class="alert">' . e($msg) . '</div>';
    }
    echo '<form method="post" action="/login/staff"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    echo '<div class="form-grid"><div><label>ایمیل یا موبایل</label><input type="text" name="login" value="09120000002" required autofocus></div>';
    echo '<div><label>رمز عبور</label><input type="password" name="password" value="1234" required></div></div>';
    echo '<p class="hint" style="margin-top:8px;">در دمو رمز همه نقش‌ها ۱۲۳۴ است. پیامک واقعی ارسال نمی‌شود.</p>';
    echo '<div class="actions"><button class="btn btn-primary" type="submit" style="width:100%;">ورود با رمز</button></div></form>';
    echo '</div></div></body></html>';
}

function render_login_customer(): void
{
    head('ورود مشتری | سرزمین هارد', 'skin-login');
    $msg = flash();
    $otpMode = isset($_GET['otp']);
    echo '<div class="login-page"><div class="panel login-card"><div class="brand-center"><h1>کارتابل مشتری</h1>';
    echo '<p class="p-lead">شماره موبایل ثبت‌شده در پذیرش را وارد کنید. در دمو پیامک واقعی نمی‌آید؛ کد همان ۱۲۳۴ است.</p></div>';
    echo '<p class="hint" style="text-align:center;margin:0 0 12px;"><a href="/">→ بازگشت</a></p>';
    if ($msg) {
        echo '<div class="alert">' . e($msg) . '</div>';
    }
    echo '<form method="post" action="/login/customer"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="otp_demo" value="1">';
    $phone = $_SESSION['pending_customer_phone'] ?? '09120000001';
    echo '<label>شماره موبایل<input type="tel" name="phone" value="' . e($phone) . '" required dir="ltr"></label>';
    if ($otpMode) {
        echo '<label>کد ۶ رقمی (دمو: ۱۲۳۴)<input type="text" name="otp" value="1234" required></label>';
    }
    echo '<button class="btn btn-primary" type="submit" style="width:100%;margin-top:10px;">ورود به کارتابل (دمو)</button></form>';
    echo '<p class="hint" style="text-align:center;margin-top:10px;">نمونه مشتری: ۰۹۱۲۰۰۰۰۰۰۱</p></div></div></body></html>';
}

function render_login_trainee(): void
{
    head('ورود کارآموز | سرزمین هارد', 'skin-login');
    $msg = flash();
    echo '<div class="login-page"><div class="panel login-card"><h1>ورود کارآموز</h1>';
    if ($msg) {
        echo '<div class="alert">' . e($msg) . '</div>';
    }
    echo '<form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    echo '<label>موبایل<input name="login" value="09120000008" required></label>';
    echo '<label>رمز<input type="password" name="password" value="1234" required></label>';
    echo '<button class="btn btn-primary" type="submit" style="width:100%;margin-top:10px;">ورود</button></form>';
    echo '<p class="hint"><a href="/">بازگشت</a></p></div></div></body></html>';
}

function render_staff_shell(array $u, string $title, string $html): void
{
    head($title, 'skin-staff');
    $msg = flash();
    echo '<div class="app-shell"><div class="win-app-caption"><div class="win-app-caption-title">';
    echo '<img class="brand-logo" src="' . e(cfg('logo')) . '" alt="">سرزمین هارد — سیستم مدیریت تعمیرات</div>';
    echo '<div class="win-app-caption-user">' . e($u['name'] ?? '') . ' · ' . e($u['role_label'] ?? '') . ' <a class="win-caption-btn" href="/logout">خروج</a></div></div>';
    echo '<nav class="win-menubar">';
    foreach (staff_menu() as $item) {
        $on = str_starts_with(path(), $item['href']) || (path() === '/s/desk' && $item['href'] === '/s/desk' && path() === $item['href']);
        // simpler active: exact or prefix for children groups
        $active = (path() === $item['href']) || (isset($item['children']) && str_starts_with(path(), rtrim($item['href'], '/')));
        if ($item['href'] === '/s/desk') {
            $active = path() === '/s/desk';
        }
        echo '<a class="' . ($active ? 'is-on' : '') . '" href="' . e($item['href']) . '">' . e($item['label']) . '</a>';
    }
    echo '</nav><main class="win-main"><div class="page-head"><h1>' . e($title) . '</h1>';
    echo '<p class="lead">سیستم مدیریت تعمیرات — حالت دمو برای تست مشتری</p></div>';
    if ($msg) {
        echo '<div class="alert alert-ok">' . e($msg) . '</div>';
    }
    echo $html;
    echo '</main><div class="win-status">demo.hdd-land.ir · نمونه محصول support.hdd-land.ir</div></div>';
    echo '<nav class="staff-tabbar mobile-only">';
    foreach ([['میز', '/s/desk'], ['پذیرش', '/s/reception'], ['ارجاع', '/s/referral'], ['هزینه', '/s/cost'], ['بیشتر', '/s/settings']] as $t) {
        echo '<a href="' . e($t[1]) . '"><span>' . e(substr($t[0], 0, 3)) . '</span><small>' . e($t[0]) . '</small></a>';
    }
    echo '</nav></body></html>';
}

function render_staff(string $p, array $u): void
{
    global $store;
    $tickets = $store->all('SELECT t.*, c.name AS customer_name, c.phone AS customer_phone FROM tickets t JOIN customers c ON c.id=t.customer_id ORDER BY t.id DESC');

    if ($p === '/s/desk' || $p === '/s/repair-shop') {
        $stats = [
            'پذیرش' => $store->scalar("SELECT COUNT(*) FROM tickets WHERE status='پذیرش'"),
            'ارجاع/تعمیر' => $store->scalar("SELECT COUNT(*) FROM tickets WHERE status IN ('ارجاع‌شده','دست تعمیر')"),
            'تأیید هزینه' => $store->scalar("SELECT COUNT(*) FROM tickets WHERE status LIKE '%هزینه%'"),
            'اعلان‌ها' => $store->scalar('SELECT COUNT(*) FROM notifications WHERE is_read=0'),
        ];
        $html = '<div class="stat-grid">';
        foreach ($stats as $k => $v) {
            $html .= '<div class="stat-card"><span>' . e($k) . '</span><strong>' . (int) $v . '</strong><small>قبض / مورد</small></div>';
        }
        $html .= '</div><div class="panel"><p class="lead">مسیر کوتاه قبض: پذیرش → ارجاع → گزارش کار → تأیید هزینه → تسویه/تحویل. همه منوهای بالا برای تست مشتری باز است.</p>';
        $html .= '<div class="quick-links">';
        foreach ([['پذیرش', '/s/reception'], ['ارجاع', '/s/referral'], ['تأیید هزینه', '/s/cost'], ['انبار', '/s/parts'], ['حسابداری', '/s/accounting'], ['گزارش‌ها', '/s/reports'], ['ریست دمو', '/s/tools']] as $q) {
            $html .= '<a class="btn btn-secondary" href="' . e($q[1]) . '">' . e($q[0]) . '</a> ';
        }
        $html .= '</div></div>';
        render_staff_shell($u, 'میز کار', $html);
        return;
    }

    if ($p === '/s/reception') {
        $html = '<div class="panel"><h3>پذیرش جدید</h3><form method="post"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="action" value="create">';
        $html .= '<div class="form-grid"><div><label>نام مشتری</label><input name="customer_name" required></div>';
        $html .= '<div><label>موبایل</label><input name="customer_mobile" required dir="ltr"></div>';
        $html .= '<div style="grid-column:1/-1"><label>دستگاه / ایراد</label><input name="device" required></div></div>';
        $html .= '<button class="btn btn-primary" type="submit" style="margin-top:10px;">ثبت قبض</button></form></div>';
        $html .= tickets_table($tickets);
        render_staff_shell($u, 'پذیرش', $html);
        return;
    }

    if ($p === '/s/referral') {
        $html = '<div class="panel"><h3>کارتابل ارجاع</h3><p class="muted">تأیید دریافت و دستگاه‌های دست تعمیر — نسخه دمو.</p>';
        foreach ($tickets as $t) {
            if (! in_array($t['status'], ['پذیرش', 'ارجاع‌شده', 'دست تعمیر'], true)) {
                continue;
            }
            $html .= '<div class="row-card"><strong>' . e($t['code']) . '</strong> · ' . e($t['device']);
            $html .= '<div class="muted">' . e($t['status']) . ' — ' . e($t['customer_name']) . ' — تعمیرکار: ' . e($t['technician'] ?: 'هنوز نیست') . '</div>';
            $html .= '<form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="code" value="' . e($t['code']) . '">';
            if ($t['status'] === 'پذیرش' || $t['status'] === 'ارجاع‌شده') {
                $html .= '<input type="hidden" name="action" value="assign"><button class="btn btn-secondary" type="submit">ارجاع به تعمیرکار</button>';
            } else {
                $html .= '<input type="hidden" name="action" value="accept"><button class="btn btn-ghost" type="submit">تأیید دریافت</button>';
            }
            $html .= '</form></div>';
        }
        $html .= '</div>';
        render_staff_shell($u, 'ارجاع', $html);
        return;
    }

    if ($p === '/s/cost') {
        $html = '<div class="panel"><h3>کارتابل تأیید هزینه</h3>';
        foreach ($tickets as $t) {
            $html .= '<div class="row-card"><strong>' . e($t['code']) . '</strong> · ' . e($t['device']);
            $html .= '<div class="muted">' . e($t['status']) . ' — ' . ($t['amount'] ? money((int) $t['amount']) . ' (دمو)' : 'بدون مبلغ') . '</div>';
            $html .= '<form method="post" style="display:inline;margin-left:6px">' . csrf_field();
            $html .= '<input type="hidden" name="code" value="' . e($t['code']) . '"><input type="hidden" name="amount" value="' . (int) ($t['amount'] ?: 8500000) . '">';
            $html .= '<input type="hidden" name="action" value="send"><button class="btn btn-secondary" type="submit">ارسال لینک</button></form>';
            $html .= '<form method="post" style="display:inline">' . csrf_field();
            $html .= '<input type="hidden" name="code" value="' . e($t['code']) . '"><input type="hidden" name="action" value="approve"><button class="btn btn-primary" type="submit">تأیید مشتری (دمو)</button></form></div>';
        }
        $html .= '</div>';
        render_staff_shell($u, 'تأیید هزینه', $html);
        return;
    }

    if ($p === '/s/notifications') {
        $rows = $store->all('SELECT * FROM notifications ORDER BY id DESC');
        $html = '<div class="panel"><h3>اعلان‌ها</h3><table class="compact-table"><thead><tr><th>عنوان</th><th>متن</th><th>زمان</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['title']) . '</td><td>' . e($r['body']) . '</td><td>' . e($r['created_at']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'اعلان‌ها', $html);
        return;
    }

    if ($p === '/s/daily-logs') {
        $rows = $store->all('SELECT * FROM daily_logs ORDER BY id DESC');
        $html = '<div class="panel"><h3>ثبت امروز</h3><form method="post">' . csrf_field() . '<input type="hidden" name="action" value="add">';
        $html .= '<div class="form-grid"><div><label>خدمت</label><input name="service" value="تست / گزارش کار" required></div>';
        $html .= '<div style="grid-column:1/-1"><label>توضیح</label><textarea name="body" rows="2"></textarea></div></div>';
        $html .= '<button class="btn btn-primary" type="submit" style="margin-top:8px;">ثبت در دفتر روز</button></form></div>';
        $html .= '<div class="panel"><table class="compact-table"><thead><tr><th>کاربر</th><th>خدمت</th><th>توضیح</th><th>زمان</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['user_name']) . '</td><td>' . e($r['service']) . '</td><td>' . e($r['body']) . '</td><td>' . e($r['created_at']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'دفتر روز', $html);
        return;
    }

    if ($p === '/s/customers') {
        $rows = $store->all('SELECT * FROM customers ORDER BY id DESC');
        $html = '<div class="panel"><h3>فهرست مشتریان</h3><table class="compact-table"><thead><tr><th>نام</th><th>موبایل</th><th>آدرس</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['name']) . '</td><td dir="ltr">' . e($r['phone']) . '</td><td>' . e($r['address']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'مشتریان', $html);
        return;
    }

    if ($p === '/s/parts') {
        $rows = $store->all('SELECT * FROM parts ORDER BY id');
        $html = '<div class="panel"><h3>میز انبار</h3><table class="compact-table"><thead><tr><th>کد</th><th>نام</th><th>موجودی</th><th>قیمت</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['code']) . '</td><td>' . e($r['name']) . '</td><td>' . (int) $r['stock'] . '</td><td>' . money((int) $r['price']) . '</td><td>';
            $html .= '<form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="action" value="adjust"><input type="hidden" name="id" value="' . (int) $r['id'] . '">';
            $html .= '<button class="btn btn-ghost" name="delta" value="1" type="submit">+۱</button> ';
            $html .= '<button class="btn btn-ghost" name="delta" value="-1" type="submit">−۱</button></form></td></tr>';
        }
        $html .= '</tbody></table><p class="muted">زیرمنوهای واقعی سیستم: رسید ورود، حواله خروج، کارتکس، ارزش موجودی، انبارهای چندگانه — در دمو روی همین میز خلاصه شده‌اند.</p></div>';
        render_staff_shell($u, 'انبار', $html);
        return;
    }

    if ($p === '/s/employees') {
        $rows = $store->all('SELECT * FROM users ORDER BY id');
        $html = '<div class="panel"><h3>کارتابل کارمند</h3><table class="compact-table"><thead><tr><th>نام</th><th>موبایل</th><th>نقش</th><th>وضعیت</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['name']) . '</td><td dir="ltr">' . e($r['phone']) . '</td><td>' . e($r['role_label']) . '</td><td>' . ($r['is_active'] ? 'فعال' : 'غیرفعال') . '</td></tr>';
        }
        $html .= '</tbody></table><p class="muted">در نسخه اصلی: دسترسی‌ها، ورود SMS/رمز، SMS خوش‌آمد.</p></div>';
        render_staff_shell($u, 'کارمندان', $html);
        return;
    }

    if ($p === '/s/interns' || $p === '/s/intern-portal') {
        $rows = $store->all('SELECT * FROM interns ORDER BY id');
        $html = '<div class="panel"><h3>کارتابل کارآموز / پرتال</h3><table class="compact-table"><thead><tr><th>نام</th><th>موبایل</th><th>بخش</th><th>بازه</th><th>وضعیت</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['name']) . '</td><td dir="ltr">' . e($r['phone']) . '</td><td>' . e($r['department']) . '</td><td>' . e($r['start_date'] . ' تا ' . $r['end_date']) . '</td><td>' . e($r['status']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'کارآموز', $html);
        return;
    }

    if ($p === '/s/technicians') {
        $html = '<div class="panel"><h3>تخصص و کمیسیون تعمیرکار</h3><table class="compact-table"><thead><tr><th>نام</th><th>تخصص</th><th>کمیسیون</th></tr></thead><tbody>';
        $html .= '<tr><td>علی تعمیرکار</td><td>هارد و دیتا ریکاوری</td><td>۳۰٪</td></tr>';
        $html .= '<tr><td>سارا فنی</td><td>SSD / لپ‌تاپ</td><td>۲۵٪</td></tr>';
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'تعمیرکاران', $html);
        return;
    }

    if ($p === '/s/sms') {
        $rows = $store->all('SELECT * FROM events ORDER BY id DESC LIMIT 30');
        $html = '<div class="panel"><h3>گزارش پیامک / رویدادهای دمو</h3><p class="muted">پیامک واقعی ارسال نمی‌شود — فقط لاگ دمو.</p><table class="compact-table"><thead><tr><th>قبض</th><th>متن</th><th>زمان</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . e($r['ticket_code']) . '</td><td>' . e($r['body']) . '</td><td>' . e($r['created_at']) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'پیامک‌ها', $html);
        return;
    }

    if ($p === '/s/accounting') {
        $sum = (int) $store->scalar('SELECT COALESCE(SUM(amount),0) FROM tickets WHERE status LIKE "%تأیید%"');
        $html = '<div class="stat-grid"><div class="stat-card"><span>درآمد تأییدشده (دمو)</span><strong>' . money($sum) . '</strong></div>';
        $html .= '<div class="stat-card"><span>بدهکاران</span><strong>۲ مشتری</strong></div>';
        $html .= '<div class="stat-card"><span>اسناد روزنامه</span><strong>۱۲</strong></div></div>';
        $html .= '<div class="panel"><h3>میز حسابداری</h3><ul class="demo-list"><li>اسناد روزنامه</li><li>سرفصل حساب‌ها</li><li>دفتر معین</li><li>تراز آزمایشی</li><li>بدهکاران</li><li>سند دستی</li></ul><p class="muted">در نسخه اصلی همه این صفحات عملیاتی‌اند؛ اینجا برای دموی فروش خلاصه شده‌اند.</p></div>';
        render_staff_shell($u, 'حسابداری', $html);
        return;
    }

    if ($p === '/s/reports') {
        $html = '<div class="panel"><h3>گزارش‌ها</h3><div class="quick-links">';
        foreach (['عملکرد تعمیرکاران', 'گزارش مشتریان', 'کالای خرج‌شده', 'عملیات کارگاه', 'ارجاع / محل دستگاه', 'صندوق و دریافت‌ها', 'تأیید فیش بانکی', 'پیام مشتری', 'گزارش پیامک'] as $r) {
            $html .= '<span class="chip">' . e($r) . '</span> ';
        }
        $html .= '</div><table class="compact-table" style="margin-top:12px"><thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead><tbody>';
        foreach ($store->all('SELECT status, COUNT(*) AS c FROM tickets GROUP BY status') as $row) {
            $html .= '<tr><td>' . e($row['status']) . '</td><td>' . (int) $row['c'] . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        render_staff_shell($u, 'گزارش‌ها', $html);
        return;
    }

    if ($p === '/s/tools') {
        $html = '<div class="panel"><h3>ابزارهای سیستم / نگهداری</h3>';
        $html .= '<p>درایور داده فعال: <strong>' . e($store->driver()) . '</strong></p>';
        $html .= '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="reset">';
        $html .= '<button class="btn btn-danger" type="submit">ریست کامل داده دمو</button></form>';
        $html .= '<p class="muted">بعد از ریست، قبض‌های پیش‌فرض HL-1405-101…104 و کاربران دمو برمی‌گردند.</p></div>';
        render_staff_shell($u, 'ابزارهای سیستم', $html);
        return;
    }

    if ($p === '/s/settings') {
        $html = '<div class="panel"><h3>تنظیمات سیستم (پیش‌نمایش دمو)</h3><ul class="demo-list">';
        foreach (['تنظیمات فاکتور و برند', 'درگاه پرداخت (غیرفعال در دمو)', 'SMS نیازپرداز (غیرفعال)', 'خدمات مشمول تأیید هزینه', 'تنظیمات دفتر روز', 'پروفایل من'] as $i) {
            $html .= '<li>' . e($i) . '</li>';
        }
        $html .= '</ul><p class="muted">تغییرات تنظیمات در دمو ذخیره نمایشی دارند و روی سایت واقعی اثر ندارند.</p></div>';
        render_staff_shell($u, 'تنظیمات', $html);
        return;
    }

    if ($p === '/s/delivery') {
        $ready = array_values(array_filter($tickets, fn ($t) => in_array($t['status'], ['هزینه تأیید شد', 'آماده تحویل'], true)));
        $html = '<div class="panel"><h3>تحویل گروهی</h3>';
        if (! $ready) {
            $html .= '<p class="muted">قبضی در وضعیت آماده/تأیید هزینه نیست. از کارتابل هزینه یک قبض را تأیید کنید.</p>';
        }
        foreach ($ready as $t) {
            $html .= '<div class="row-card"><strong>' . e($t['code']) . '</strong> — ' . e($t['customer_name']);
            $html .= '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="deliver"><input type="hidden" name="code" value="' . e($t['code']) . '">';
            $html .= '<button class="btn btn-primary" type="submit">ثبت خروج</button></form></div>';
        }
        $html .= '</div>';
        render_staff_shell($u, 'تحویل گروهی', $html);
        return;
    }

    if ($p === '/s/customer-preview') {
        $html = '<div class="panel"><h3>پیش‌نمایش کارتابل مشتری</h3>' . tickets_table($tickets) . '<p><a class="btn btn-secondary" href="/login/customer">ورود با نقش مشتری</a></p></div>';
        render_staff_shell($u, 'کارتابل مشتری', $html);
        return;
    }

    if ($p === '/s/online-store') {
        $html = '<div class="panel"><h3>سایت فروشگاهی (ویترین دمو)</h3><div class="store-grid">';
        foreach ([['هارد اکسترنال ۲ ترابایت', 'WD-2T', 6], ['SSD M.2 NVMe ۱ ترابایت', 'NV-1T', 4], ['کابل USB 3.0', 'USB-30', 20]] as $item) {
            $html .= '<div class="store-card"><strong>' . e($item[0]) . '</strong><div class="muted">' . e($item[1]) . ' · موجودی ' . $item[2] . '</div><button class="btn btn-secondary" type="button">ثبت سفارش دمو</button></div>';
        }
        $html .= '</div></div>';
        render_staff_shell($u, 'سایت فروشگاهی', $html);
        return;
    }

    if ($p === '/s/corporate') {
        $html = '<div class="panel"><h3>سایت شرکتی</h3><p>ویترین برند، نمونه کار تعمیر و فرم استعلام — روی همان پوسته سیستم تعمیرات.</p>';
        $html .= '<form onsubmit="alert(\'استعلام دمو ثبت شد\');return false;"><div class="form-grid"><div><label>نام</label><input required></div><div><label>موبایل</label><input required></div><div style="grid-column:1/-1"><label>پیام</label><textarea rows="2"></textarea></div></div>';
        $html .= '<button class="btn btn-primary" type="submit" style="margin-top:8px;">ارسال استعلام</button></form></div>';
        render_staff_shell($u, 'سایت شرکتی', $html);
        return;
    }

    if ($p === '/s/booking') {
        $html = '<div class="panel"><h3>سایت خدماتی / نوبت‌دهی</h3><form onsubmit="alert(\'نوبت دمو رزرو شد\');return false;">';
        $html .= '<label>خدمت<select><option>مشاوره بازیابی اطلاعات</option><option>ارزیابی تعمیر هارد</option><option>جلسه آموزش تعمیر SSD</option></select></label>';
        $html .= '<label>ساعت<select><option>دوشنبه ۲۰ مهر — ۰۹:۳۰</option><option>دوشنبه ۲۰ مهر — ۱۱:۰۰</option></select></label>';
        $html .= '<button class="btn btn-primary" type="submit" style="margin-top:8px;">رزرو نوبت دمو</button></form></div>';
        render_staff_shell($u, 'سایت خدماتی / نوبت‌دهی', $html);
        return;
    }

    http_response_code(404);
    render_staff_shell($u, 'یافت نشد', '<div class="panel"><p>این بخش در نقشه منو هست؛ مسیر پیدا نشد.</p></div>');
}

function render_customer(string $p, array $cu): void
{
    global $store;
    $tickets = $store->all('SELECT t.* FROM tickets t JOIN customers c ON c.id=t.customer_id WHERE c.phone=? ORDER BY t.id DESC', [$cu['phone']]);
    head('کارتابل مشتری', 'skin-portal');
    echo '<div class="portal-wrap"><header class="p-head"><strong>کارتابل تعمیرات</strong><span>' . e($cu['name']) . '</span><a href="/logout">خروج</a></header>';
    echo '<nav class="p-tabs"><a href="/c/home">میز کار</a><a href="/c/tickets">قبض‌ها</a><a href="/c/approvals">تأیید هزینه</a><a href="/c/pay">پرداخت</a></nav>';
    echo '<main class="p-main">';
    if ($p === '/c/home') {
        echo '<div class="panel"><h2>سلام ' . e($cu['name']) . '</h2><p class="muted">منوی سریع کارتابل مشتری (دمو)</p>';
        echo '<div class="quick-links"><a class="btn btn-secondary" href="/c/tickets">همه قبض‌ها</a> <a class="btn btn-secondary" href="/c/approvals">تأیید هزینه‌ها</a> <a class="btn btn-secondary" href="/c/pay">پرداخت آنلاین</a></div></div>';
    } elseif ($p === '/c/tickets') {
        echo tickets_table($tickets, false);
    } elseif ($p === '/c/approvals') {
        echo '<div class="panel"><h3>تأیید هزینه‌ها</h3>';
        foreach ($tickets as $t) {
            if (! str_contains($t['status'], 'هزینه')) {
                continue;
            }
            echo '<div class="row-card"><strong>' . e($t['code']) . '</strong> — ' . e($t['status']) . ' — ' . money((int) $t['amount']) . '</div>';
        }
        echo '</div>';
    } elseif ($p === '/c/pay') {
        echo '<div class="panel"><h3>پرداخت آنلاین (دمو)</h3><p class="muted">درگاه واقعی غیرفعال است. فقط برای نمایش مسیر فروش.</p><button class="btn btn-primary" type="button" onclick="alert(\'پرداخت دمو — بدون کسر وجه\')">پرداخت آزمایشی</button></div>';
    } else {
        echo '<div class="panel"><p>صفحه پیدا نشد</p></div>';
    }
    echo '</main></div></body></html>';
}

function tickets_table(array $tickets, bool $showCustomer = true): string
{
    $html = '<div class="panel"><h3>لیست قبض‌ها</h3><table class="compact-table"><thead><tr><th>کد</th>';
    if ($showCustomer) {
        $html .= '<th>مشتری</th>';
    }
    $html .= '<th>دستگاه</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($tickets as $t) {
        $html .= '<tr><td>' . e($t['code']) . '</td>';
        if ($showCustomer) {
            $html .= '<td>' . e($t['customer_name'] ?? '') . '</td>';
        }
        $html .= '<td>' . e($t['device']) . '</td><td>' . e($t['status']) . '</td></tr>';
    }
    $html .= '</tbody></table></div>';

    return $html;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
