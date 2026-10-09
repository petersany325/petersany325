<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/Router.php';

$router = new Router();

$router->get('/install', function () {
    if (Installer::isInstalled()) {
        redirect('/login');
    }
    view('install', ['title' => 'نصب سامانه']);
});

$router->post('/install', function () {
    if (Installer::isInstalled()) {
        redirect('/login');
    }
    verify_csrf();
    try {
        Installer::run([
            'host' => trim($_POST['db_host'] ?? 'localhost'),
            'name' => trim($_POST['db_name'] ?? ''),
            'user' => trim($_POST['db_user'] ?? ''),
            'pass' => (string) ($_POST['db_pass'] ?? ''),
            'base_url' => trim($_POST['base_url'] ?? ''),
        ], trim($_POST['admin_name'] ?? 'مدیر'), trim($_POST['admin_email'] ?? ''), (string) ($_POST['admin_pass'] ?? ''));
        flash('ok', 'نصب با موفقیت انجام شد. وارد شوید.');
        redirect('/login');
    } catch (Throwable $e) {
        flash('err', 'خطا در نصب: ' . $e->getMessage());
        redirect('/install');
    }
});

$router->get('/login', function () {
    if (!Installer::isInstalled()) {
        redirect('/install');
    }
    if (current_user()) {
        redirect(wants_mobile_ui() ? '/m' : '/');
    }
    if (wants_mobile_ui()) {
        redirect('/m/login');
    }
    $tab = $_GET['tab'] ?? 'email';
    if (!in_array($tab, ['email', 'phone', 'otp'], true)) {
        $tab = 'email';
    }
    view('login', ['title' => 'ورود', 'tab' => $tab, 'otpPhone' => $_SESSION['otp_phone'] ?? '']);
});

$router->post('/login', function () {
    verify_csrf();
    $mode = (string) ($_POST['mode'] ?? 'email');
    $ok = false;
    if ($mode === 'phone') {
        $ok = attempt_login_phone(trim($_POST['phone'] ?? ''), (string) ($_POST['password'] ?? ''));
    } else {
        $ok = attempt_login(trim($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
    }
    if ($ok) {
        Audit::log('auth.login');
        redirect(wants_mobile_ui() ? '/m' : '/');
    }
    flash('err', 'اطلاعات ورود نادرست است یا کاربر غیرفعال است.');
    redirect(wants_mobile_ui() ? '/m/login' : '/login?tab=' . urlencode($mode === 'phone' ? 'phone' : 'email'));
});

$router->post('/login/otp/send', function () {
    verify_csrf();
    $phone = trim($_POST['phone'] ?? '');
    $res = create_login_otp($phone);
    $_SESSION['otp_phone'] = Sms::normalizeMobile($phone);
    flash($res['ok'] ? 'ok' : 'err', $res['message']);
    $toMobile = ($_POST['redirect'] ?? '') === 'mobile' || wants_mobile_ui();
    redirect($toMobile ? '/m/login?tab=otp&mobile=1' : '/login?tab=otp');
});

$router->post('/login/otp/verify', function () {
    verify_csrf();
    $phone = trim($_POST['phone'] ?? ($_SESSION['otp_phone'] ?? ''));
    $code = trim($_POST['code'] ?? '');
    $toMobile = ($_POST['redirect'] ?? '') === 'mobile' || wants_mobile_ui();
    if (verify_login_otp($phone, $code)) {
        Audit::log('auth.login.otp');
        unset($_SESSION['otp_phone']);
        redirect($toMobile ? '/m' : '/');
    }
    flash('err', 'کد تأیید نادرست یا منقضی است.');
    redirect($toMobile ? '/m/login?tab=otp&mobile=1' : '/login?tab=otp');
});

$router->get('/logout', function () {
    if (current_user()) {
        Audit::log('auth.logout');
    }
    logout_user();
    redirect(wants_mobile_ui() ? '/m/login' : '/login');
});

require __DIR__ . '/../app/routes_mobile.php';
require __DIR__ . '/../app/routes_app.php';
require __DIR__ . '/../app/routes_settings.php';
require __DIR__ . '/../app/routes_visitors.php';

// Auto-send phones from desktop home into mobile app (unless desktop mode forced)
$router->get('/go-mobile', function () {
    $_SESSION['ui_mode'] = 'mobile';
    redirect(current_user() ? '/m' : '/m/login');
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
