<?php
declare(strict_types=1);

/** @var Router $router */

$router->get('/settings', function () {
    require_login();
    view('settings_hub', ['title' => 'مرکز تنظیمات', 'nav' => 'settings_hub']);
});

$router->get('/settings/invoice', function () {
    Permission::require('invoices.manage');
    $s = InvoiceSettings::all();
    view('settings_invoice', compact('s') + ['title' => 'فاکتور و چاپ پیشرفته', 'nav' => 'settings_invoice']);
});

$router->post('/settings/invoice', function () {
    Permission::require('invoices.manage');
    verify_csrf();
    InvoiceSettings::save($_POST);
    Audit::log('settings.invoice');
    flash('ok', 'تنظیمات فاکتور و چاپ ذخیره شد.');
    redirect('/settings/invoice');
});

$router->get('/settings/sms', function () {
    Permission::require('sms.manage');
    $s = Sms::config();
    view('settings_sms', compact('s') + ['title' => 'پیامک نیازپرداز', 'nav' => 'settings_sms']);
});

$router->post('/settings/sms', function () {
    Permission::require('sms.manage');
    verify_csrf();
    $op = (string) ($_POST['op'] ?? 'save');
    if ($op === 'test') {
        $res = Sms::send(trim($_POST['test_phone'] ?? ''), trim($_POST['test_text'] ?? 'تست'));
        flash($res['ok'] ? 'ok' : 'err', $res['message']);
        redirect('/settings/sms');
    }
    SettingsStore::setMany([
        'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
        'sms_provider' => 'niazpardaz',
        'sms_mode' => ($_POST['sms_mode'] ?? 'classic') === 'apikey' ? 'apikey' : 'classic',
        'sms_username' => trim($_POST['sms_username'] ?? ''),
        'sms_password' => (string) ($_POST['sms_password'] ?? ''),
        'sms_api_key' => trim($_POST['sms_api_key'] ?? ''),
        'sms_from' => trim($_POST['sms_from'] ?? ''),
        'sms_base_url' => trim($_POST['sms_base_url'] ?? 'https://panel.niazpardaz-sms.com'),
        'sms_otp_template' => trim($_POST['sms_otp_template'] ?? ''),
        'sms_invoice_template' => trim($_POST['sms_invoice_template'] ?? ''),
    ]);
    Audit::log('settings.sms');
    flash('ok', 'تنظیمات پیامک نیازپرداز ذخیره شد.');
    redirect('/settings/sms');
});

$router->get('/settings/license', function () {
    Permission::require('license.manage');
    $lic = License::current();
    $fingerprint = License::fingerprint();
    $issuedKey = $_SESSION['issued_license_key'] ?? null;
    unset($_SESSION['issued_license_key']);
    view('settings_license', compact('lic', 'fingerprint', 'issuedKey') + ['title' => 'لایسنس و فروش', 'nav' => 'settings_license']);
});

$router->post('/settings/license', function () {
    Permission::require('license.manage');
    verify_csrf();
    $op = (string) ($_POST['op'] ?? 'activate');
    if ($op === 'clear') {
        License::clear();
        Database::query('INSERT INTO license_events (action, detail, user_id) VALUES (?,?,?)', ['clear', 'trial', current_user()['id'] ?? null]);
        flash('ok', 'لایسنس پاک شد — حالت آزمایشی.');
        redirect('/settings/license');
    }
    if ($op === 'issue') {
        $plans = [
            'starter' => ['seats' => 3, 'modules' => ['accounting', 'reports', 'invoices']],
            'pro' => ['seats' => 10, 'modules' => ['accounting', 'treasury', 'reports', 'invoices', 'sms']],
            'enterprise' => ['seats' => 50, 'modules' => ['*']],
        ];
        $plan = $plans[$_POST['plan'] ?? 'pro'] ?? $plans['pro'];
        $key = License::issue([
            'type' => 'full',
            'customer' => trim($_POST['customer'] ?? ''),
            'seats' => $plan['seats'],
            'modules' => $plan['modules'],
            'fingerprint' => ($_POST['bind'] ?? 'any') === 'fp' ? License::fingerprint() : 'ANY',
            'domain' => 'ANY',
            'expires_at' => date('Y-m-d', strtotime('+1 year')),
        ]);
        $_SESSION['issued_license_key'] = $key;
        Database::query('INSERT INTO license_events (action, detail, user_id) VALUES (?,?,?)', [
            'issue', trim($_POST['customer'] ?? ''), current_user()['id'] ?? null,
        ]);
        flash('ok', 'کلید لایسنس صادر شد.');
        redirect('/settings/license');
    }
    $res = License::activate((string) ($_POST['license_key'] ?? ''));
    Database::query('INSERT INTO license_events (action, detail, user_id) VALUES (?,?,?)', [
        $res['ok'] ? 'activate' : 'activate_fail', $res['message'], current_user()['id'] ?? null,
    ]);
    flash($res['ok'] ? 'ok' : 'err', $res['message']);
    redirect('/settings/license');
});

$router->get('/settings/profile', function () {
    require_login();
    $uid = (int) current_user()['id'];
    $me = Database::query('SELECT id, name, email, phone, role FROM users WHERE id=?', [$uid])->fetch();
    view('settings_profile', compact('me') + ['title' => 'پروفایل من', 'nav' => 'settings_profile']);
});

$router->post('/settings/profile', function () {
    require_login();
    verify_csrf();
    $uid = (int) current_user()['id'];
    $name = trim($_POST['name'] ?? '');
    $phone = Sms::normalizeMobile(trim($_POST['phone'] ?? ''));
    $pass = (string) ($_POST['new_pass'] ?? '');
    $pass2 = (string) ($_POST['new_pass2'] ?? '');
    if ($name === '') {
        flash('err', 'نام الزامی است');
        redirect('/settings/profile');
    }
    if ($pass !== '' && $pass !== $pass2) {
        flash('err', 'تکرار رمز یکسان نیست');
        redirect('/settings/profile');
    }
    Database::query('UPDATE users SET name=?, phone=? WHERE id=?', [$name, $phone !== '' ? $phone : null, $uid]);
    if ($pass !== '') {
        Database::query('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $uid]);
    }
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['phone'] = $phone !== '' ? $phone : null;
    Audit::log('settings.profile');
    flash('ok', 'پروفایل ذخیره شد.');
    redirect('/settings/profile');
});

$router->get('/invoices/print', function () {
    Permission::require('invoices.print');
    $id = (int) ($_GET['id'] ?? 0);
    $invoice = Database::query(
        'SELECT i.*, p.name party_name FROM invoices i LEFT JOIN parties p ON p.id=i.party_id WHERE i.id=?',
        [$id]
    )->fetch();
    if (!$invoice) {
        flash('err', 'فاکتور یافت نشد');
        redirect('/invoices');
    }
    $s = InvoiceSettings::all();
    // Bare print document (no Windows chrome)
    require __DIR__ . '/../views/print_invoice.php';
    exit;
});
