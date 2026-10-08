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
    view('login', ['title' => 'ورود']);
});

$router->post('/login', function () {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');
    if (attempt_login($email, $pass)) {
        Audit::log('auth.login');
        redirect(wants_mobile_ui() ? '/m' : '/');
    }
    flash('err', 'ایمیل یا رمز عبور نادرست است.');
    redirect(wants_mobile_ui() ? '/m/login' : '/login');
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

// Auto-send phones from desktop home into mobile app (unless desktop mode forced)
$router->get('/go-mobile', function () {
    $_SESSION['ui_mode'] = 'mobile';
    redirect(current_user() ? '/m' : '/m/login');
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
