<?php

declare(strict_types=1);

function logo_src(): string
{
    return 'https://support.hdd-land.ir/images/logo.png?v=1';
}

function badge_class(string $status): string
{
    return match ($status) {
        'intake' => 'badge-received',
        'referred' => 'badge-waiting',
        'with_tech' => 'badge-repairing',
        'cost_pending' => 'badge-waiting',
        'cost_ok', 'ready' => 'badge-ready',
        'delivered' => 'badge-delivered',
        'cost_rejected' => 'badge-cancelled',
        default => 'badge-delivered',
    };
}

function after_login_home(): never
{
    $user = current_user();
    if (!$user) {
        redirect('/');
    }
    if ($user['role'] === 'customer') {
        redirect('/s/repair-shop/customer');
    }
    if ($user['role'] === 'trainee') {
        redirect('/s/repair-shop/trainee');
    }
    redirect('/s/repair-shop');
}

function page_home(): void
{
    if (current_user()) {
        after_login_home();
    }
    $wanted = preg_replace('/[^a-z-]/', '', (string) ($_GET['site'] ?? ''));
    $doors = '';
    foreach (demo_roles() as $role) {
        $href = demo_url('/login/' . $role['key'] . ($wanted !== '' ? '?site=' . rawurlencode($wanted) : ''));
        $doors .= '<a class="gate-card ' . h($role['css']) . '" href="' . h($href) . '">'
            . '<span class="gate-ico">' . h($role['letter']) . '</span>'
            . '<span class="gate-text"><strong>' . h($role['title']) . '</strong><span>' . h($role['lead']) . '</span></span>'
            . '<span class="gate-go">←</span></a>';
    }
    $body = '<div class="gate-wrap"><div class="gate">
        <div class="gate-brand">
            <div class="gate-logo"><img src="' . h(logo_src()) . '" alt="سرزمین هارد"></div>
            <h1>سرزمین هارد</h1>
            <p>سیستم مدیریت تعمیرات — نوع ورود را انتخاب کنید</p>
        </div>
        <div class="gate-cards">' . $doors . '</div>
        <div class="gate-foot">demo.hdd-land.ir · نمونه زنده همان سیستم support.hdd-land.ir</div>
    </div></div>';
    render_layout('سرزمین هارد | انتخاب ورود', $body, 'gate');
}

function page_login(string $role, string $site): void
{
    $roles = demo_roles();
    if ($role === '' || !isset($roles[$role])) {
        page_home();
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
    $back = '<p class="hint" style="text-align:center;margin:0 0 12px;"><a href="' . h(demo_url('/')) . '" style="font-size:12px;font-weight:700;color:#0f766e;">→ بازگشت به انتخاب ورود</a></p>';
    $action = demo_url('/login/' . $role);
    $csrf = '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">' . $siteField;

    if ($role === 'customer') {
        $body = '<div class="p-auth"><div class="p-auth-card">
            <div class="p-brand">
                <div class="p-logo"><img src="' . h(logo_src()) . '" alt="سرزمین هارد"></div>
                <div><strong>سرزمین هارد</strong><span>کارتابل مشتری</span></div>
            </div>
            ' . $back . '
            <h1>ورود با موبایل</h1>
            <p class="p-lead">شماره موبایل ثبت‌شده در پذیرش را وارد کنید. در دمو پیامک واقعی نمی‌آید؛ کد همان ۱۲۳۴ است.</p>
            <form method="post" action="' . h($action) . '" class="p-form">
                ' . $csrf . '
                <input type="hidden" name="otp_demo" value="1">
                <label>شماره موبایل
                    <input type="tel" name="phone" value="' . h($preset) . '" placeholder="09xxxxxxxxx" required autofocus dir="ltr" inputmode="tel" autocomplete="tel">
                </label>
                <button class="p-btn primary" type="submit">ورود به کارتابل (دمو)</button>
            </form>
            <p class="hint" style="text-align:center;margin-top:10px;">نمونه مشتری: ۰۹۱۲۰۰۰۰۰۰۱</p>
        </div></div>';
        render_layout('ورود مشتری | سرزمین هارد', $body, 'portal-auth');
        return;
    }

    $who = $role === 'trainee' ? 'ورود کارآموز — دفتر روز' : 'ورود کارمند — موبایل و کامپیوتر';
    $body = '<div class="login-page"><div class="panel login-card">
        <div class="brand-center">
            <img class="brand-logo-lg" src="' . h(logo_src()) . '" alt="سرزمین هارد" width="96" height="96">
            <h1>سرزمین هارد</h1>
            <p>' . h($who) . '</p>
        </div>
        <p class="hint" style="text-align:center;margin:0 0 10px;">کامپیوتر تشخیص داده شد — منوی کامل ویندوزی کارمند</p>
        ' . $back . '
        <div class="login-tabs">
            <button type="button" class="login-tab" data-login-tab="otp">موبایل / SMS</button>
            <button type="button" class="login-tab is-active" data-login-tab="pass">رمز عبور</button>
        </div>
        <div id="tab-pass" class="login-pane">
            <form method="post" action="' . h($action) . '">
                ' . $csrf . '
                <div class="form-grid">
                    <div><label>ایمیل یا موبایل</label>
                        <input type="text" name="login" value="' . h($preset) . '" placeholder="09..." required autofocus autocomplete="username"></div>
                    <div><label>رمز عبور</label>
                        <input type="password" name="password" value="1234" required autocomplete="current-password"></div>
                </div>
                <p class="hint" style="margin-top:8px;">در دمو رمز همه نقش‌ها ۱۲۳۴ است. پیامک واقعی ارسال نمی‌شود.</p>
                <div class="actions"><button class="btn btn-primary" type="submit" style="width:100%;">ورود با رمز</button></div>
            </form>
        </div>
        <div id="tab-otp" class="login-pane hidden">
            <form method="post" action="' . h($action) . '">
                ' . $csrf . '
                <input type="hidden" name="otp_demo" value="1">
                <p class="hint" style="margin-top:0;">مناسب وب‌سرویس موبایل. در دمو کد پیامک ساخته نمی‌شود و همان ورود آزمایشی است.</p>
                <div class="form-grid"><div><label>شماره موبایل</label>
                    <input type="text" name="phone" value="' . h($preset) . '" placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;" inputmode="tel"></div></div>
                <div class="actions"><button class="btn btn-primary" type="submit" style="width:100%;">ورود دمو بدون پیامک</button></div>
            </form>
        </div>
    </div></div>
    <script>
    document.querySelectorAll("[data-login-tab]").forEach(function(btn){
      btn.addEventListener("click", function(){
        var name=btn.getAttribute("data-login-tab");
        document.getElementById("tab-pass").classList.toggle("hidden", name!=="pass");
        document.getElementById("tab-otp").classList.toggle("hidden", name!=="otp");
        document.querySelectorAll("[data-login-tab]").forEach(function(b){ b.classList.toggle("is-active", b===btn); });
      });
    });
    </script>';
    render_layout('ورود | سرزمین هارد', $body, 'login');
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

function staff_nav(string $section): string
{
    $items = [
        '' => 'میز کار',
        'reception' => 'پذیرش',
        'referral' => 'ارجاع',
        'cost' => 'تأیید هزینه',
        'customer' => 'کارتابل مشتری',
        'trainee' => 'کارآموز',
    ];
    $html = '';
    foreach ($items as $key => $label) {
        $on = $section === $key ? ' is-on' : '';
        $href = demo_url('/s/repair-shop' . ($key !== '' ? '/' . $key : ''));
        $html .= '<a class="' . $on . '" href="' . h($href) . '">' . h($label) . '</a>';
    }
    $html .= '<a href="' . h(demo_url('/s/online-store')) . '">فروشگاه</a>';
    $html .= '<a href="' . h(demo_url('/s/corporate')) . '">شرکتی</a>';
    $html .= '<a href="' . h(demo_url('/s/booking')) . '">نوبت</a>';
    return $html;
}

function win_frame(string $title, string $inner, string $note = 'سیستم مدیریت تعمیرات'): string
{
    return '<div class="win-frame"><div class="win-titlebar"><span>' . h($title) . '</span><span class="muted">' . h($note) . '</span></div><div class="win-body">' . $inner . '</div></div>';
}

function page_repair(array $site, string $section, array $user): void
{
    if ($user['role'] === 'customer') {
        page_customer_portal($section, $user);
        return;
    }

    if ($section === '' || $section === 'desk') {
        $counts = [];
        foreach (db()->query("SELECT status, COUNT(*) c FROM demo_receipts WHERE site_slug='repair-shop' GROUP BY status")->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        $tiles = '';
        foreach (['intake' => 'پذیرش', 'referred' => 'ارجاع', 'with_tech' => 'دست تعمیر', 'cost_pending' => 'تأیید هزینه'] as $st => $label) {
            $n = $counts[$st] ?? 0;
            $tiles .= '<a class="shortcut-card tone-blue" href="' . h(demo_url('/s/repair-shop/' . ($st === 'intake' ? 'reception' : ($st === 'with_tech' ? 'referral' : ($st === 'cost_pending' ? 'cost' : 'referral'))))) . '">'
                . '<span class="shortcut-icon">' . $n . '</span><span class="shortcut-text"><strong>' . h($label) . '</strong><small>' . $n . ' قبض</small></span></a>';
        }
        $inner = '<p class="hint">مسیر کوتاه قبض همان محصول support.hdd-land.ir است: پذیرش → ارجاع → تأیید هزینه → مشتری.</p>
            <div class="shortcut-grid">' . $tiles . '</div>
            <div class="panel" style="margin-top:8px;"><h2>میانبر کارتابل</h2>
            <div class="shortcut-grid">
              <a class="shortcut-card tone-teal" href="' . h(demo_url('/s/repair-shop/reception')) . '"><span class="shortcut-icon">۱</span><span class="shortcut-text"><strong>پذیرش جدید</strong><small>ثبت قبض دستگاه</small></span></a>
              <a class="shortcut-card tone-amber" href="' . h(demo_url('/s/repair-shop/referral')) . '"><span class="shortcut-icon">۲</span><span class="shortcut-text"><strong>کارتابل ارجاع</strong><small>دست کیست</small></span></a>
              <a class="shortcut-card tone-green" href="' . h(demo_url('/s/repair-shop/cost')) . '"><span class="shortcut-icon">۳</span><span class="shortcut-text"><strong>تأیید هزینه</strong><small>لینک مشتری</small></span></a>
              <a class="shortcut-card" href="' . h(demo_url('/s/repair-shop/customer')) . '"><span class="shortcut-icon">۴</span><span class="shortcut-text"><strong>نمای مشتری</strong><small>پیگیری قبض</small></span></a>
            </div></div>';
        if ($user['role'] === 'staff') {
            $inner .= '<form method="post" action="' . h(demo_url('/reset')) . '" style="margin-top:8px;"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '"><button class="btn" type="submit">بازنشانی دادهٔ آزمایشی</button></form>';
        }
        render_layout('میز کار', win_frame('میز کار', $inner), 'staff', 'میز کار', $section);
        return;
    }

    if ($section === 'reception') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC")->fetchAll();
        $form = $user['role'] === 'staff'
            ? '<form method="post" class="stack"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                <label>نام مشتری</label><input name="customer_name" required>
                <label>موبایل</label><input name="customer_mobile" required inputmode="numeric">
                <label>دستگاه / ایراد</label><input name="device" required>
                <button class="btn btn-primary" type="submit">ثبت قبض</button></form>'
            : '<p class="hint">ثبت قبض فقط با ورود کارمند است.</p>';
        $inner = '<div class="split-2"><div class="panel"><h2>پذیرش جدید</h2>' . $form . '</div><div class="panel"><h2>لیست قبض‌ها</h2>' . receipt_table($rows) . '</div></div>';
        render_layout('پذیرش', win_frame('پذیرش', $inner), 'staff', 'پذیرش', $section);
        return;
    }

    if ($section === 'referral') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND status IN ('intake','referred','with_tech') ORDER BY id DESC")->fetchAll();
        $html = '';
        foreach ($rows as $row) {
            $html .= '<div class="panel"><strong>' . h($row['code']) . '</strong> · ' . h($row['device'])
                . '<br><span class="badge ' . badge_class($row['status']) . '">' . h(status_label($row['status'])) . '</span>'
                . '<p class="hint">' . h($row['customer_name']) . ' — تعمیرکار: ' . h($row['technician'] ?: 'هنوز نیست') . '</p>';
            if ($user['role'] === 'staff' && $row['status'] === 'intake') {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <input type="hidden" name="action" value="assign">
                  <input name="technician" value="حسین تعمیرکار">
                  <button class="btn btn-primary" type="submit">ارجاع به تعمیرکار</button></form>';
            }
            if (in_array($user['role'], ['staff', 'trainee'], true) && $row['status'] === 'referred') {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-primary" type="submit">تأیید دریافت دستگاه</button></form>';
            }
            $html .= '</div>';
        }
        render_layout('ارجاع', win_frame('کارتابل ارجاع', $html !== '' ? $html : '<p class="empty">قبضی برای ارجاع نیست.</p>'), 'staff', 'ارجاع', $section);
        return;
    }

    if ($section === 'cost') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND status IN ('with_tech','cost_pending','cost_ok','cost_rejected') ORDER BY id DESC")->fetchAll();
        $html = '';
        foreach ($rows as $row) {
            $html .= '<div class="panel"><strong>' . h($row['code']) . '</strong> · ' . h($row['device'])
                . '<p><span class="badge ' . badge_class($row['status']) . '">' . h(status_label($row['status'])) . '</span> ' . h(money_label((int) $row['labor_amount'])) . '</p>';
            if ($user['role'] === 'staff' && in_array($row['status'], ['with_tech', 'cost_rejected'], true)) {
                $html .= '<form method="post"><input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                  <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                  <label>مبلغ اجرت (تومان)</label>
                  <input name="amount" value="' . h((string) ($row['labor_amount'] ?: 7500000)) . '" inputmode="numeric">
                  <button class="btn btn-primary" type="submit">ارسال لینک تأیید به مشتری</button></form>
                  <p class="hint">پیامک واقعی خاموش است؛ فقط رویداد دمو نوشته می‌شود.</p>';
            }
            $html .= '</div>';
        }
        render_layout('تأیید هزینه', win_frame('تأیید هزینه', $html !== '' ? $html : '<p class="empty">هنوز دستگاهی دست تعمیر نیست.</p>'), 'staff', 'تأیید هزینه', $section);
        return;
    }

    if ($section === 'customer') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC")->fetchAll();
        render_layout('کارتابل مشتری', win_frame('پیش‌نمایش کارتابل مشتری', receipt_table($rows) . '<p class="hint" style="margin-top:8px;">برای دیدن چهره مشتری، با نقش مشتری از درِ ورود وارد شوید.</p>'), 'staff', 'کارتابل مشتری', $section);
        return;
    }

    if ($section === 'trainee') {
        $rows = db()->query("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' ORDER BY id DESC LIMIT 8")->fetchAll();
        $note = $user['role'] === 'trainee'
            ? '<p class="hint">کارآموز هزینه و تحویل را عوض نمی‌کند؛ فقط مشاهده و تأیید دریافت در ارجاع.</p>'
            : '<p class="hint">پیش‌نمایش پرتال کارآموز.</p>';
        render_layout('کارآموز', win_frame('دفتر روز کارآموز', $note . receipt_table($rows)), 'staff', 'کارآموز', $section);
        return;
    }

    http_response_code(404);
    render_layout('یافت نشد', win_frame('یافت نشد', '<p>این بخش در دمو کوتاه نیست.</p>'), 'staff', 'یافت نشد', $section);
}

function page_customer_portal(string $section, array $user): void
{
    $st = db()->prepare("SELECT * FROM demo_receipts WHERE site_slug='repair-shop' AND (customer_mobile=? OR customer_name=?) ORDER BY id DESC");
    $st->execute([$user['mobile'], $user['name']]);
    $rows = $st->fetchAll();
    $tickets = '';
    foreach ($rows as $row) {
        $tickets .= '<article class="p-ticket"><div class="p-ticket-top"><strong>' . h($row['code']) . '</strong><span class="p-badge ' . (str_contains($row['status'], 'ok') ? 'tone-green' : 'tone-amber') . '">' . h(status_label($row['status'])) . '</span></div>'
            . '<div class="p-ticket-title">' . h($row['device']) . '</div>'
            . '<div class="p-ticket-meta"><span>' . h(money_label((int) $row['labor_amount'])) . '</span><span>' . h($row['note'] ?? '') . '</span></div>';
        if ($row['status'] === 'cost_pending') {
            $tickets .= '<form method="post" action="' . h(demo_url('/s/repair-shop/customer')) . '" style="margin-top:8px;display:flex;gap:6px;">
                <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
                <input type="hidden" name="receipt_id" value="' . (int) $row['id'] . '">
                <button class="p-btn primary" name="decision" value="approved" type="submit">تأیید هزینه</button>
                <button class="p-btn ghost" name="decision" value="rejected" type="submit">رد هزینه</button></form>';
        }
        $tickets .= '</article>';
    }
    $body = '<div class="p-hero"><div><h1>کارتابل مشتری</h1><p>پیگیری قبض، تأیید هزینه و پرداخت — همان محصول support.hdd-land.ir</p></div>
        <div class="p-hero-stat"><strong>' . count($rows) . '</strong><span>قبض</span></div></div>
        <div class="p-section"><h2>قبض‌های شما</h2><div class="p-ticket-list">'
        . ($tickets !== '' ? $tickets : '<p class="empty">قبضی برای این مشتری نیست. با موبایل ۰۹۱۲۰۰۰۰۰۰۱ وارد شوید.</p>')
        . '</div></div>';
    render_layout('کارتابل مشتری', $body, 'portal', 'کارتابل مشتری', $section ?: 'customer');
}

function page_store(array $site, array $user): void
{
    $products = db()->query("SELECT * FROM demo_products WHERE site_slug='online-store'")->fetchAll();
    $orders = db()->query("SELECT * FROM demo_orders WHERE site_slug='online-store' ORDER BY id DESC")->fetchAll();
    $cards = '<div class="shortcut-grid">';
    foreach ($products as $p) {
        $cards .= '<div class="panel"><strong>' . h($p['name']) . '</strong><p class="hint">' . h($p['sku']) . ' · موجودی ' . (int) $p['stock'] . '</p>
          <p><span class="badge badge-ready">' . h($p['price_label']) . '</span></p>
          <form method="post" action="' . h(demo_url('/s/online-store/order')) . '">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <input type="hidden" name="sku" value="' . h($p['sku']) . '">
            <button class="btn btn-primary" type="submit">ثبت سفارش دمو</button>
          </form></div>';
    }
    $cards .= '</div>';
    $ot = '<div class="table-wrap"><table><thead><tr><th>کد</th><th>مشتری</th><th>اقلام</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $ot .= '<tr><td>' . h($o['code']) . '</td><td>' . h($o['customer_name']) . '</td><td>' . h($o['items_text']) . '</td><td>' . h(status_label($o['status'])) . '</td></tr>';
    }
    $ot .= '</tbody></table></div>';
    render_layout($site['title'], win_frame($site['title'], $cards . '<div class="panel"><h2>سفارش‌ها</h2>' . $ot . '</div>'), 'staff', $site['title'], 'store');
}

function page_corporate(array $site, array $user): void
{
    $leads = $user['role'] === 'staff'
        ? db()->query("SELECT * FROM demo_leads WHERE site_slug='corporate' ORDER BY id DESC")->fetchAll()
        : [];
    $list = '';
    foreach ($leads as $row) {
        $list .= '<p><strong>' . h($row['name']) . '</strong> · ' . h($row['mobile']) . '<br><span class="hint">' . h($row['message']) . '</span></p>';
    }
    $inner = '<div class="split-2"><div class="panel"><h2>خدمات سازمانی</h2><p class="hint">ویترین برند، نمونه کار تعمیر و فرم استعلام — روی همان پوسته سیستم تعمیرات.</p></div>
        <div class="panel"><h2>استعلام</h2>
          <form method="post" action="' . h(demo_url('/s/corporate/lead')) . '" class="stack">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <label>نام</label><input name="name" value="' . h($user['name']) . '">
            <label>موبایل</label><input name="mobile" value="' . h($user['mobile']) . '">
            <label>پیام</label><textarea name="message" rows="4">درخواست دمو سایت شرکتی</textarea>
            <button class="btn btn-primary" type="submit">ارسال استعلام</button>
          </form></div></div>' . ($list !== '' ? '<div class="panel"><h2>استعلام‌های ثبت‌شده</h2>' . $list . '</div>' : '');
    render_layout($site['title'], win_frame($site['title'], $inner), 'staff', $site['title'], 'corporate');
}

function page_booking(array $site, array $user): void
{
    $rows = db()->query("SELECT * FROM demo_bookings WHERE site_slug='booking' ORDER BY id DESC")->fetchAll();
    $table = '<div class="table-wrap"><table><thead><tr><th>خدمت</th><th>ساعت</th><th>نام</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $table .= '<tr><td>' . h($row['service']) . '</td><td>' . h($row['slot_label']) . '</td><td>' . h($row['customer_name']) . '</td><td>' . h(status_label($row['status'])) . '</td></tr>';
    }
    $table .= '</tbody></table></div>';
    $inner = '<div class="split-2"><div class="panel"><h2>رزرو نوبت</h2>
          <form method="post" action="' . h(demo_url('/s/booking/book')) . '" class="stack">
            <input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">
            <label>خدمت</label>
            <select name="service"><option>مشاوره بازیابی اطلاعات</option><option>ارزیابی تعمیر هارد</option><option>جلسه آموزش تعمیر SSD</option></select>
            <label>ساعت</label>
            <select name="slot"><option>دوشنبه ۲۰ مهر — ۰۹:۳۰</option><option>دوشنبه ۲۰ مهر — ۱۱:۰۰</option><option>سه‌شنبه ۲۱ مهر — ۱۶:۰۰</option></select>
            <button class="btn btn-primary" type="submit">رزرو</button>
          </form></div><div class="panel"><h2>نوبت‌ها</h2>' . $table . '</div></div>';
    render_layout($site['title'], win_frame($site['title'], $inner), 'staff', $site['title'], 'booking');
}

function receipt_table(array $rows): string
{
    if (!$rows) {
        return '<p class="empty">موردی نیست.</p>';
    }
    $html = '<div class="table-wrap"><table><thead><tr><th>کد</th><th>مشتری</th><th>دستگاه</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr><td>' . h($row['code']) . '</td><td>' . h($row['customer_name']) . '</td><td>' . h($row['device']) . '</td><td><span class="badge ' . badge_class($row['status']) . '">' . h(status_label($row['status'])) . '</span></td></tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function render_setup(string $error): void
{
    http_response_code(503);
    $body = '<div class="gate-wrap"><div class="gate"><div class="gate-brand"><h1>سرزمین هارد</h1><p>دمو هنوز به بانک وصل نیست</p></div>
      <div class="gate-cards"><div class="panel"><p class="alert alert-error">' . h($error) . '</p></div></div></div></div>';
    render_layout('راه‌اندازی دمو', $body, 'gate');
}

function render_layout(string $title, string $body, string $skin = 'staff', string $pageCaption = '', string $section = ''): void
{
    $user = current_user();
    $flash = flash_take();
    $flashHtml = '';
    if ($flash) {
        $cls = ($flash['type'] ?? '') === 'err' ? 'alert-error' : 'alert-success';
        $flashHtml = '<div class="alert ' . $cls . '">' . h((string) $flash['text']) . '</div>';
    }
    $css = demo_url('/assets/demo.css') . '?v=10';
    $ribbon = '<div class="demo-ribbon">DEMO — نمونه زنده سیستم مدیریت تعمیرات · بدون پیامک و پرداخت واقعی · جدا از فروشگاه</div>';
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    $skinClass = 'skin-' . $skin;
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
      <meta name="theme-color" content="#2b3340">
      <meta name="robots" content="noindex,nofollow">
      <title>' . h($title) . '</title>
      <link rel="icon" href="https://support.hdd-land.ir/favicon.ico?v=hd1">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="' . h($css) . '"></head><body class="' . h($skinClass) . '">' . $ribbon;

    if ($skin === 'gate' || $skin === 'login' || $skin === 'portal-auth') {
        echo $flashHtml . $body . '</body></html>';
        return;
    }

    if ($skin === 'portal') {
        $who = $user ? h($user['name']) : '';
        echo '<header class="p-top"><div class="p-brand-mini"><img src="' . h(logo_src()) . '" alt=""><div><div class="p-hello">' . $who . '</div><div class="p-sub">کارتابل مشتری</div></div></div>
          <a class="win-caption-btn" href="' . h(demo_url('/logout')) . '">خروج</a></header>'
            . $flashHtml . $body
            . '<nav class="p-tabbar">
                <a class="is-on" href="' . h(demo_url('/s/repair-shop/customer')) . '"><span>☰</span>پیگیری</a>
                <a href="' . h(demo_url('/s/repair-shop/customer')) . '"><span>﷼</span>هزینه</a>
                <a href="' . h(demo_url('/s/repair-shop/customer')) . '"><span>↓</span>پرداخت</a>
                <a href="' . h(demo_url('/logout')) . '"><span>×</span>خروج</a>
              </nav></body></html>';
        return;
    }

    $nav = staff_nav($section);
    $who = $user ? h($user['name']) . ' · ' . h($user['title']) : '';
    $tab = '';
    foreach (['' => 'میز', 'reception' => 'پذیرش', 'referral' => 'ارجاع', 'cost' => 'هزینه'] as $key => $label) {
        $on = $section === $key ? ' is-on' : '';
        $tab .= '<a class="' . $on . '" href="' . h(demo_url('/s/repair-shop' . ($key !== '' ? '/' . $key : ''))) . '">' . h($label) . '</a>';
    }
    echo '<div class="app-shell">
      <div class="win-app-caption">
        <div class="win-app-caption-title"><img class="brand-logo" src="' . h(logo_src()) . '" alt="">سرزمین هارد — سیستم مدیریت تعمیرات</div>
        <div class="win-app-caption-user">' . $who . ' <a class="win-caption-btn" href="' . h(demo_url('/logout')) . '">خروج</a></div>
      </div>
      <nav class="win-menubar">' . $nav . '</nav>
      <div class="app-toolbar"><div class="page-caption">' . h($pageCaption !== '' ? $pageCaption : $title) . '</div></div>
      <div class="app-workspace">' . $flashHtml . $body . '</div>
      <div class="app-statusbar"><span>demo.hdd-land.ir</span><span>نمونه محصول support.hdd-land.ir</span></div>
      <nav class="staff-tabbar">' . $tab . '</nav>
    </div></body></html>';
}
