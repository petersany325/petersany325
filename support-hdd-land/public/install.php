<?php

/**
 * Web installer (no SSH / Terminal needed) — vBulletin-style.
 * Open: https://your-domain.com/install.php
 */

declare(strict_types=1);

use HddLand\Install\WebInstaller;

error_reporting(E_ALL);
ini_set('display_errors', '0');

require __DIR__.'/../install/WebInstaller.php';

$installer = new WebInstaller(dirname(__DIR__));
session_start();

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function detect_domain(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\\d+$/', '', $host) ?? $host;

    return strtolower(preg_replace('/^www\\./', '', $host) ?? $host);
}

function detect_app_url(): string
{
    $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme.'://'.$host;
}

$step = (int) ($_GET['step'] ?? $_POST['step'] ?? 1);
$step = max(1, min(5, $step));
$error = null;
$success = null;
$state = $_SESSION['install'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'reset_install') {
    $lock = $installer->lockPath();
    if (is_file($lock)) {
        @unlink($lock);
    }
    unset($_SESSION['install']);
    header('Location: install.php?step=1');
    exit;
}

if ($installer->isInstalled() && $step !== 5) {
    $step = 5;
    $success = 'این سایت قبلاً نصب شده است.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'requirements' && $step === 1) {
        if (! $installer->requirementsPassed()) {
            $error = 'هنوز بعضی پیش‌نیازها برقرار نیست.';
        } else {
            $state['requirements_ok'] = true;
            $_SESSION['install'] = $state;
            header('Location: install.php?step=2');
            exit;
        }
    }

    if ($action === 'license_request_otp' && $step === 2) {
        $key = (string) ($_POST['license_key'] ?? '');
        $domain = trim((string) ($_POST['domain'] ?? detect_domain()));
        $server = WebInstaller::SELLER_LICENSE_SERVER;
        $res = $installer->requestLicenseOtp($key, $domain, $server);
        if (! ($res['ok'] ?? false)) {
            $error = $res['message'] ?? 'لایسنس نامعتبر است.';
            $state['purchase_url'] = $res['purchase_url'] ?? WebInstaller::SELLER_PURCHASE_URL;
        } elseif (($res['demo'] ?? false) === true) {
            $state['license'] = $res['payload'];
            $state['license_server'] = $server;
            unset($state['license_otp']);
            $_SESSION['install'] = $state;
            header('Location: install.php?step=3');
            exit;
        } else {
            $state['license_otp'] = [
                'license_key' => strtoupper(trim($key)),
                'domain' => $domain,
                'phone_masked' => (string) ($res['phone_masked'] ?? ''),
                'awaiting_code' => true,
            ];
            $state['license_server'] = $server;
            $state['purchase_url'] = $res['purchase_url'] ?? WebInstaller::SELLER_PURCHASE_URL;
            $_SESSION['install'] = $state;
            $success = $res['message'] ?? 'کد تأیید پیامک شد.';
        }
    }

    if ($action === 'license_confirm_otp' && $step === 2) {
        $otp = $state['license_otp'] ?? [];
        $key = (string) ($_POST['license_key'] ?? ($otp['license_key'] ?? ''));
        $domain = trim((string) ($_POST['domain'] ?? ($otp['domain'] ?? detect_domain())));
        $server = WebInstaller::SELLER_LICENSE_SERVER;
        $code = trim((string) ($_POST['code'] ?? ''));
        $res = $installer->confirmLicenseOtp($key, $domain, $server, '', $code);
        if (! ($res['ok'] ?? false)) {
            $error = $res['message'] ?? 'کد تأیید نامعتبر است.';
            $state['license_otp'] = array_merge($otp, [
                'license_key' => strtoupper(trim($key)),
                'domain' => $domain,
                'awaiting_code' => true,
            ]);
            $state['license_server'] = $server;
            $_SESSION['install'] = $state;
        } else {
            $state['license'] = $res['payload'];
            $state['license_server'] = $server;
            unset($state['license_otp']);
            $_SESSION['install'] = $state;
            header('Location: install.php?step=3');
            exit;
        }
    }

    if ($action === 'license_reset' && $step === 2) {
        unset($state['license_otp'], $state['license']);
        $_SESSION['install'] = $state;
        header('Location: install.php?step=2');
        exit;
    }

    if ($action === 'database' && $step === 3) {
        $mode = (string) ($_POST['db_mode'] ?? 'cpanel');
        $state['db_mode'] = $mode;

        if ($mode === 'cpanel') {
            $cpanelInput = [
                'cpanel_host' => trim((string) ($_POST['cpanel_host'] ?? '')),
                'cpanel_user' => trim((string) ($_POST['cpanel_user'] ?? '')),
                'cpanel_password' => (string) ($_POST['cpanel_password'] ?? ''),
                'db_name' => trim((string) ($_POST['auto_db_name'] ?? '')),
                'db_user' => trim((string) ($_POST['auto_db_user'] ?? '')),
                'db_password' => (string) ($_POST['auto_db_password'] ?? ''),
                'mysql_host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')),
                'mysql_port' => trim((string) ($_POST['db_port'] ?? '3306')),
            ];
            $state['cpanel'] = [
                'cpanel_host' => $cpanelInput['cpanel_host'],
                'cpanel_user' => $cpanelInput['cpanel_user'],
                'db_name' => $cpanelInput['db_name'],
                'db_user' => $cpanelInput['db_user'],
                'mysql_host' => $cpanelInput['mysql_host'],
                'mysql_port' => $cpanelInput['mysql_port'],
            ];
            $res = $installer->createDatabaseViaCpanel($cpanelInput);
            if (! ($res['ok'] ?? false)) {
                $error = $res['message'] ?? 'ساخت دیتابیس از طریق cPanel ناموفق بود.';
                $state['details'] = $res['details'] ?? [];
                $_SESSION['install'] = $state;
            } else {
                $state['db'] = $res['db'];
                $state['details'] = $res['details'] ?? [];
                $_SESSION['install'] = $state;
                header('Location: install.php?step=4');
                exit;
            }
        } else {
            $db = [
                'host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')),
                'port' => trim((string) ($_POST['db_port'] ?? '3306')),
                'database' => trim((string) ($_POST['db_database'] ?? '')),
                'username' => trim((string) ($_POST['db_username'] ?? '')),
                'password' => (string) ($_POST['db_password'] ?? ''),
            ];
            $res = $installer->testDatabaseWithHostFallback($db['host'], $db['port'], $db['database'], $db['username'], $db['password']);
            if (! ($res['ok'] ?? false)) {
                $error = $installer->friendlyDbError($res['message'] ?? 'اتصال دیتابیس ناموفق.');
                $state['db'] = $db;
                $_SESSION['install'] = $state;
            } else {
                if (! empty($res['host'])) {
                    $db['host'] = (string) $res['host'];
                }
                $state['db'] = $db;
                $_SESSION['install'] = $state;
                header('Location: install.php?step=4');
                exit;
            }
        }
    }

    if ($action === 'install' && $step === 4) {
        if (empty($state['license']) || empty($state['db'])) {
            $error = 'مراحل لایسنس و دیتابیس را کامل کنید.';
            $step = empty($state['license']) ? 2 : 3;
        } else {
            $appName = trim((string) ($_POST['app_name'] ?? 'تعمیرگاه'));
            $appUrl = rtrim(trim((string) ($_POST['app_url'] ?? detect_app_url())), '/');
            $company = [
                'shop_name' => $appName,
                'tagline' => trim((string) ($_POST['shop_tagline'] ?? 'سیستم مدیریت تعمیرات')),
                'phones' => trim((string) ($_POST['shop_phones'] ?? '')),
                'address' => trim((string) ($_POST['shop_address'] ?? '')),
                'footer' => trim((string) ($_POST['shop_footer'] ?? '')),
                'terms' => trim((string) ($_POST['shop_terms'] ?? '')),
            ];
            $admin = [
                'name' => trim((string) ($_POST['admin_name'] ?? 'مدیر')),
                'email' => trim((string) ($_POST['admin_email'] ?? '')),
                'phone' => trim((string) ($_POST['admin_phone'] ?? '')),
                'password' => (string) ($_POST['admin_password'] ?? ''),
                'app_url' => $appUrl,
                'app_name' => $appName,
                // Default on: safe for re-install on same DB after a failed attempt
                'wipe_database' => ! isset($_POST['wipe_database']) || (string) $_POST['wipe_database'] === '1',
            ];
            $logo = null;
            if (! empty($_FILES['shop_logo']['tmp_name']) && is_uploaded_file((string) $_FILES['shop_logo']['tmp_name'])) {
                $mime = (string) ($_FILES['shop_logo']['type'] ?? '');
                $okMime = in_array($mime, ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'], true)
                    || preg_match('/\.(png|jpe?g|webp|gif)$/i', (string) ($_FILES['shop_logo']['name'] ?? ''));
                if (! $okMime) {
                    $error = 'لوگوی شرکت باید تصویر باشد (PNG/JPG/WEBP).';
                } elseif ((int) ($_FILES['shop_logo']['size'] ?? 0) > 3 * 1024 * 1024) {
                    $error = 'حجم لوگو حداکثر ۳ مگابایت باشد.';
                } else {
                    $logo = [
                        'tmp' => (string) $_FILES['shop_logo']['tmp_name'],
                        'name' => (string) ($_FILES['shop_logo']['name'] ?? 'logo.png'),
                        'mime' => $mime,
                    ];
                }
            }
            if ($error === null && ($admin['email'] === '' || $admin['password'] === '' || strlen($admin['password']) < 6)) {
                $error = 'ایمیل مدیر و رمز حداقل ۶ کاراکتری الزامی است.';
            } elseif ($error === null && $appName === '') {
                $error = 'نام شرکت / تعمیرگاه الزامی است.';
            } elseif ($error === null) {
                $db = $state['db'];
                $license = $state['license'];
                $appKey = $installer->generateAppKey();
                try {
                    $installer->clearBootstrapCaches();

                    // Re-test right before writing .env / migrate (catches wrong password early).
                    $dbTest = $installer->testDatabaseWithHostFallback(
                        (string) $db['host'],
                        (string) $db['port'],
                        (string) $db['database'],
                        (string) $db['username'],
                        (string) $db['password']
                    );
                    if (! ($dbTest['ok'] ?? false)) {
                        $hint = ' — در حال استفاده: '
                            .(string) ($db['username'] ?? '?').' @ '.(string) ($db['database'] ?? '?')
                            .' / host='.(string) ($db['host'] ?? '?');
                        throw new RuntimeException($installer->friendlyDbError($dbTest['message'] ?? 'اتصال دیتابیس ناموفق.').$hint);
                    }
                    if (! empty($dbTest['host'])) {
                        $db['host'] = (string) $dbTest['host'];
                        $state['db'] = $db;
                        $_SESSION['install'] = $state;
                    }

                    $installer->writeEnv([
                        'APP_NAME' => $appName,
                        'APP_ENV' => 'production',
                        'APP_KEY' => $appKey,
                        'APP_DEBUG' => 'false',
                        'APP_URL' => $appUrl,
                        'LOG_CHANNEL' => 'stack',
                        'LOG_LEVEL' => 'error',
                        'DB_CONNECTION' => 'mysql',
                        'DB_HOST' => $db['host'],
                        'DB_PORT' => $db['port'],
                        'DB_DATABASE' => $db['database'],
                        'DB_USERNAME' => $db['username'],
                        'DB_PASSWORD' => $db['password'],
                        'SESSION_DRIVER' => 'file',
                        'CACHE_STORE' => 'file',
                        'QUEUE_CONNECTION' => 'sync',
                        'FILESYSTEM_DISK' => 'local',
                        'MAIL_MAILER' => 'log',
                        'LICENSE_KEY' => (string) ($license['license_key'] ?? ''),
                        'LICENSE_DOMAIN' => (string) ($license['domain'] ?? ''),
                        'LICENSE_TOKEN' => (string) ($license['token'] ?? ''),
                        'LICENSE_SERVER' => WebInstaller::SELLER_LICENSE_SERVER,
                        'LICENSE_PURCHASE_URL' => WebInstaller::SELLER_PURCHASE_URL,
                        'LICENSE_PLAN' => (string) ($license['plan'] ?? ''),
                        'LICENSE_PLAN_CODE' => (string) ($license['plan_code'] ?? ''),
                        'LICENSE_MONTHS' => (string) ($license['plan_months'] ?? ''),
                        'LICENSE_PRICE' => (string) ($license['price_toman'] ?? ''),
                        'LICENSE_ACTIVATED_AT' => (string) ($license['activated_at'] ?? ''),
                        'LICENSE_EXPIRES_AT' => (string) ($license['expires_at'] ?? ''),
                    ]);

                    $result = $installer->runInstall($admin, $license, $db, $company, $logo);
                    if (! ($result['ok'] ?? false)) {
                        $raw = (string) ($result['message'] ?? 'نصب ناموفق بود.');
                        $error = (str_contains($raw, '1045') || str_contains($raw, 'Access denied')
                            || str_contains($raw, '1049') || str_contains($raw, '2002'))
                            ? $installer->friendlyDbError($raw)
                            : $raw;
                        $state['details'] = $result['details'] ?? [];
                        $_SESSION['install'] = $state;
                    } else {
                        $state['done'] = true;
                        $state['admin_email'] = $admin['email'];
                        $state['details'] = $result['details'] ?? [];
                        $_SESSION['install'] = $state;
                        header('Location: install.php?step=5');
                        exit;
                    }
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$reqs = $installer->checkRequirements();
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نصب سیستم مدیریت تعمیرگاه</title>
    <style>
        :root { --bg:#eef1f5; --card:#fff; --ink:#1f2933; --muted:#667788; --line:#c9d0da; --ok:#0f6b3a; --bad:#b42318; --brand:#1d4f91; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Tahoma, Vazirmatn, sans-serif; background:linear-gradient(180deg,#e8eef8,#eef1f5 40%,#f5f7fa); color:var(--ink); min-height:100vh; }
        .wrap { max-width:720px; margin:0 auto; padding:28px 16px 48px; }
        .brand { font-size:22px; font-weight:800; margin:0 0 6px; color:var(--brand); }
        .sub { color:var(--muted); margin:0 0 18px; font-size:13px; }
        .steps { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px; }
        .steps span { background:#e7edf6; color:#334; border-radius:999px; padding:5px 10px; font-size:11px; }
        .steps span.on { background:var(--brand); color:#fff; }
        .card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:18px; box-shadow:0 10px 28px rgba(31,41,51,.06); }
        h2 { margin:0 0 10px; font-size:17px; }
        p { margin:0 0 12px; color:var(--muted); font-size:13px; line-height:1.7; }
        label { display:block; font-size:12px; margin:0 0 4px; color:#334; }
        input, select { width:100%; padding:10px 12px; border:1px solid #b7c0cc; border-radius:6px; font:inherit; margin-bottom:10px; background:#fff; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        @media (max-width:640px){ .grid{grid-template-columns:1fr;} }
        .btn { appearance:none; border:0; background:var(--brand); color:#fff; border-radius:8px; padding:11px 16px; font:inherit; cursor:pointer; font-weight:700; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .alert { padding:10px 12px; border-radius:8px; margin-bottom:12px; font-size:13px; }
        .alert.err { background:#fde8e8; color:var(--bad); border:1px solid #f3b4b4; }
        .alert.ok { background:#e8f8ef; color:var(--ok); border:1px solid #9dcfb0; }
        table { width:100%; border-collapse:collapse; font-size:12.5px; margin-bottom:12px; }
        td { padding:8px 6px; border-bottom:1px solid #e6ebf1; }
        .pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; }
        .pill.ok { background:#e8f8ef; color:var(--ok); }
        .pill.bad { background:#fde8e8; color:var(--bad); }
        code { background:#f1f4f8; padding:1px 5px; border-radius:4px; }
        .foot { margin-top:14px; font-size:11px; color:var(--muted); }
    </style>
</head>
<body>
<div class="wrap">
    <h1 class="brand">نصب سیستم مدیریت تعمیرگاه</h1>
    <p class="sub">نصب تحت وب — بدون نیاز به Terminal / SSH (مناسب هاست اشتراکی)</p>

    <div class="steps">
        <span class="<?= $step === 1 ? 'on' : '' ?>">۱) پیش‌نیاز</span>
        <span class="<?= $step === 2 ? 'on' : '' ?>">۲) لایسنس</span>
        <span class="<?= $step === 3 ? 'on' : '' ?>">۳) دیتابیس</span>
        <span class="<?= $step === 4 ? 'on' : '' ?>">۴) اطلاعات سایت</span>
        <span class="<?= $step === 5 ? 'on' : '' ?>">۵) پایان</span>
    </div>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert err"><?= h($error) ?></div>
            <?php if ($step === 3 && ! empty($state['details']) && is_array($state['details'])): ?>
                <pre style="direction:ltr;text-align:left;font-size:11px;background:#f8fafc;border:1px solid #e2e8f0;padding:10px;border-radius:8px;overflow:auto;max-height:180px;"><?= h(implode("\n", array_map('strval', $state['details']))) ?></pre>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($success && $step === 5): ?>
            <div class="alert ok"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h2>بررسی پیش‌نیازها</h2>
            <p>قبل از نصب، نسخه PHP و دسترسی نوشتن پوشه‌ها را چک کنید.</p>
            <table>
                <?php foreach ($reqs as $row): ?>
                    <tr>
                        <td><?= h($row['label']) ?><?php if (! empty($row['detail'])): ?> <span style="color:#889">(<?= h($row['detail']) ?>)</span><?php endif; ?></td>
                        <td style="width:90px;text-align:left;">
                            <span class="pill <?= $row['ok'] ? 'ok' : 'bad' ?>"><?= $row['ok'] ? 'OK' : 'رد' ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <form method="post">
                <input type="hidden" name="step" value="1">
                <input type="hidden" name="action" value="requirements">
                <button class="btn" type="submit" <?= $installer->requirementsPassed() ? '' : 'disabled' ?>>ادامه</button>
            </form>

        <?php elseif ($step === 2): ?>
            <?php
                $otpState = $state['license_otp'] ?? [];
                $awaitingCode = ! empty($otpState['awaiting_code']);
                $purchaseUrl = (string) ($state['purchase_url'] ?? 'https://hdd-land.ir');
            ?>
            <h2>فعال‌سازی لایسنس (دو مرحله)</h2>
            <p>۱) سریال را تأیید کنید تا پیامک کد از <strong>سرزمین هارد</strong> بیاید — ۲) کد را وارد کنید تا لایسنس روی این دامنه قفل شود.</p>
            <p style="margin-bottom:10px;font-size:12px;color:#667788;">ارسال پیامک از سایت شما:
                <span dir="ltr">https://support.hdd-land.ir/license/request-otp</span>
            </p>
            <p style="margin-bottom:14px;font-size:12px;color:#667788;">خرید لایسنس: <a href="<?= h($purchaseUrl) ?>" target="_blank" rel="noopener">hdd-land.ir</a></p>

            <?php if (! $awaitingCode): ?>
            <form method="post">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="action" value="license_request_otp">
                <input type="hidden" name="license_server" value="<?= h(WebInstaller::SELLER_LICENSE_SERVER) ?>">
                <label>سریال لایسنس</label>
                <input name="license_key" required placeholder="XXXX-XXXX-XXXX-XXXX" value="<?= h($_POST['license_key'] ?? ($otpState['license_key'] ?? '')) ?>" dir="ltr" style="text-align:left;letter-spacing:.06em;">
                <label>دامنه این نصب</label>
                <input name="domain" required value="<?= h($_POST['domain'] ?? ($otpState['domain'] ?? detect_domain())) ?>" dir="ltr" style="text-align:left;">
                <p style="font-size:12px;color:#667788;margin:0 0 12px;">پیامک فقط به موبایلی می‌رود که فروشنده روی همین سریال در ادمین ثبت کرده؛ اینجا شماره موبایل گرفته نمی‌شود.</p>
                <button class="btn" type="submit">تأیید سریال و ارسال پیامک به صاحب لایسنس</button>
            </form>
            <?php else: ?>
            <form method="post">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="action" value="license_confirm_otp">
                <input type="hidden" name="license_key" value="<?= h($otpState['license_key'] ?? '') ?>">
                <input type="hidden" name="domain" value="<?= h($otpState['domain'] ?? detect_domain()) ?>">
                <input type="hidden" name="license_server" value="<?= h(WebInstaller::SELLER_LICENSE_SERVER) ?>">
                <p style="margin-bottom:12px;">کد فقط به موبایل صاحب لایسنس <?= h($otpState['phone_masked'] ?? '') ?> ارسال شد. دامنه قفل: <strong dir="ltr"><?= h($otpState['domain'] ?? '') ?></strong></p>
                <label>کد تأیید پیامک</label>
                <input name="code" required inputmode="numeric" autocomplete="one-time-code" placeholder="مثلاً 12345" value="<?= h($_POST['code'] ?? '') ?>" dir="ltr" style="text-align:left;letter-spacing:.12em;font-size:18px;">
                <button class="btn" type="submit">تأیید کد و ادامه نصب</button>
            </form>
            <form method="post" style="margin-top:10px;">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="action" value="license_reset">
                <button type="submit" style="background:#fff;color:#334;border:1px solid #c9d0da;">تغییر سریال / ارسال مجدد</button>
            </form>
            <?php endif; ?>

        <?php elseif ($step === 3): ?>
            <?php
                $db = $state['db'] ?? [];
                $cp = $state['cpanel'] ?? [];
                $mode = (string) ($_POST['db_mode'] ?? ($state['db_mode'] ?? 'cpanel'));
            ?>
            <h2>ساخت / اتصال دیتابیس MySQL</h2>
            <p>حالت پیشنهادی: با یوزر cPanel، دیتابیس و کاربر MySQL را همین‌جا می‌سازد و دسترسی کامل می‌دهد.</p>
            <form method="post" id="db-form">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="action" value="database">

                <label>روش</label>
                <select name="db_mode" id="db_mode" onchange="toggleDbMode()">
                    <option value="cpanel" <?= $mode === 'cpanel' ? 'selected' : '' ?>>ساخت خودکار با cPanel (پیشنهادی)</option>
                    <option value="manual" <?= $mode === 'manual' ? 'selected' : '' ?>>دستی — دیتابیس از قبل ساخته شده</option>
                </select>

                <div id="mode-cpanel" style="<?= $mode === 'manual' ? 'display:none' : '' ?>">
                    <div class="alert ok" style="background:#ecfdf5;color:#065f46;border-color:#6ee7b7;">
                        یوزر و رمز <strong>ورود cPanel</strong> لازم است (نه فقط کاربر MySQL).
                        نصب‌کننده از API سی‌پنل دیتابیس و کاربر را می‌سازد.
                    </div>
                    <div class="grid">
                        <div>
                            <label>آدرس سرور cPanel</label>
                            <input name="cpanel_host" value="<?= h($_POST['cpanel_host'] ?? ($cp['cpanel_host'] ?? '')) ?>" dir="ltr" style="text-align:left;" placeholder="مثلاً server.irandns.com یا IP">
                            <div style="font-size:11px;color:#778;margin:-6px 0 10px;">بدون https — فقط هاست (پورت 2083 خودکار است)</div>
                        </div>
                        <div>
                            <label>یوزر cPanel</label>
                            <input name="cpanel_user" value="<?= h($_POST['cpanel_user'] ?? ($cp['cpanel_user'] ?? '')) ?>" dir="ltr" style="text-align:left;" placeholder="مثلاً hddrecov">
                        </div>
                    </div>
                    <label>رمز عبور cPanel</label>
                    <input type="password" name="cpanel_password" dir="ltr" style="text-align:left;" autocomplete="off">
                    <div class="grid">
                        <div>
                            <label>نام کوتاه دیتابیس</label>
                            <input name="auto_db_name" value="<?= h($_POST['auto_db_name'] ?? ($cp['db_name'] ?? 'repair')) ?>" dir="ltr" style="text-align:left;" placeholder="repair">
                            <div style="font-size:11px;color:#778;margin:-6px 0 10px;">سی‌پنل معمولاً به شکل <code>یوزر_نام</code> می‌سازد</div>
                        </div>
                        <div>
                            <label>نام کوتاه کاربر MySQL</label>
                            <input name="auto_db_user" value="<?= h($_POST['auto_db_user'] ?? ($cp['db_user'] ?? 'repair')) ?>" dir="ltr" style="text-align:left;" placeholder="repair">
                        </div>
                    </div>
                    <label>رمز عبور کاربر MySQL (جدید)</label>
                    <input type="password" name="auto_db_password" dir="ltr" style="text-align:left;" minlength="6" autocomplete="new-password">
                </div>

                <div id="mode-manual" style="<?= $mode === 'cpanel' ? 'display:none' : '' ?>">
                    <div class="alert err" style="background:#fff7ed;color:#9a3412;border-color:#fdba74;">
                        اگر دیتابیس را خودتان در cPanel ساخته‌اید، نام‌های کامل و رمز را اینجا وارد کنید.
                    </div>
                    <label>نام کامل دیتابیس</label>
                    <input name="db_database" value="<?= h($db['database'] ?? '') ?>" dir="ltr" style="text-align:left;" placeholder="مثلاً hddrecov_repair">
                    <div class="grid">
                        <div>
                            <label>نام کامل کاربر MySQL</label>
                            <input name="db_username" value="<?= h($db['username'] ?? '') ?>" dir="ltr" style="text-align:left;">
                        </div>
                        <div>
                            <label>رمز عبور کاربر MySQL</label>
                            <input type="password" name="db_password" value="<?= h($db['password'] ?? '') ?>" dir="ltr" style="text-align:left;">
                        </div>
                    </div>
                </div>

                <div class="grid">
                    <div>
                        <label>هاست MySQL</label>
                        <input name="db_host" value="<?= h($_POST['db_host'] ?? ($db['host'] ?? ($cp['mysql_host'] ?? '127.0.0.1'))) ?>" required dir="ltr" style="text-align:left;">
                        <div style="font-size:11px;color:#778;margin:-6px 0 10px;">اگر نشد، <code>localhost</code></div>
                    </div>
                    <div>
                        <label>پورت MySQL</label>
                        <input name="db_port" value="<?= h($_POST['db_port'] ?? ($db['port'] ?? ($cp['mysql_port'] ?? '3306'))) ?>" required dir="ltr" style="text-align:left;">
                    </div>
                </div>
                <button class="btn" type="submit" id="db-submit"><?= $mode === 'manual' ? 'تست اتصال و ادامه' : 'ساخت دیتابیس و ادامه' ?></button>
            </form>
            <script>
            function toggleDbMode() {
                var m = document.getElementById('db_mode').value;
                document.getElementById('mode-cpanel').style.display = m === 'cpanel' ? '' : 'none';
                document.getElementById('mode-manual').style.display = m === 'manual' ? '' : 'none';
                document.getElementById('db-submit').textContent = m === 'manual' ? 'تست اتصال و ادامه' : 'ساخت دیتابیس و ادامه';
            }
            </script>

        <?php elseif ($step === 4): ?>
            <h2>مشخصات شرکت و مدیر</h2>
            <p>نام و لوگوی شرکت روی کل نرم‌افزار می‌نشیند. منوهای پذیرش و تعاریف خالی نصب می‌شوند.</p>
            <?php if (! empty($state['db'])): ?>
                <p style="margin-bottom:10px;font-size:12px;color:#445;">
                    دیتابیس ذخیره‌شده:
                    <code dir="ltr"><?= h((string) ($state['db']['username'] ?? '')) ?>@<?= h((string) ($state['db']['database'] ?? '')) ?></code>
                    /
                    <code dir="ltr"><?= h((string) ($state['db']['host'] ?? '')) ?></code>
                </p>
            <?php endif; ?>
            <p style="margin-bottom:12px;"><a href="?step=3">← برگشت به تنظیم دیتابیس (ساخت خودکار cPanel)</a></p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="step" value="4">
                <input type="hidden" name="action" value="install">

                <h2 style="font-size:15px;margin:8px 0 10px;">برند شرکت</h2>
                <div class="grid">
                    <div>
                        <label>نام شرکت / تعمیرگاه</label>
                        <input name="app_name" value="<?= h($_POST['app_name'] ?? '') ?>" required placeholder="مثلاً تعمیرگاه نمونه">
                    </div>
                    <div>
                        <label>شعار کوتاه</label>
                        <input name="shop_tagline" value="<?= h($_POST['shop_tagline'] ?? 'سیستم مدیریت تعمیرات') ?>">
                    </div>
                </div>
                <label>لوگوی شرکت (PNG یا JPG)</label>
                <input type="file" name="shop_logo" accept="image/png,image/jpeg,image/webp,image/gif">
                <div style="font-size:11px;color:#778;margin:-6px 0 10px;">روی هدر، ورود، فاکتور و آیکون‌ها اعمال می‌شود.</div>
                <div class="grid">
                    <div>
                        <label>تلفن‌های شرکت</label>
                        <input name="shop_phones" value="<?= h($_POST['shop_phones'] ?? '') ?>" placeholder="۰۲۱-… / ۰۹۱۲…">
                    </div>
                    <div>
                        <label>آدرس سایت</label>
                        <input name="app_url" value="<?= h($_POST['app_url'] ?? detect_app_url()) ?>" required dir="ltr" style="text-align:left;">
                    </div>
                </div>
                <label>آدرس شرکت</label>
                <input name="shop_address" value="<?= h($_POST['shop_address'] ?? '') ?>" placeholder="شهر، خیابان، پلاک">
                <label>پاورقی فاکتور</label>
                <input name="shop_footer" value="<?= h($_POST['shop_footer'] ?? '') ?>" placeholder="اختیاری">
                <label>شرایط فاکتور / پذیرش</label>
                <input name="shop_terms" value="<?= h($_POST['shop_terms'] ?? '') ?>" placeholder="اختیاری">

                <h2 style="font-size:15px;margin:16px 0 10px;">مدیر سیستم</h2>
                <div class="grid">
                    <div>
                        <label>نام مدیر</label>
                        <input name="admin_name" value="<?= h($_POST['admin_name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>موبایل مدیر</label>
                        <input name="admin_phone" value="<?= h($_POST['admin_phone'] ?? '') ?>" required dir="ltr" style="text-align:left;">
                    </div>
                </div>
                <div class="grid">
                    <div>
                        <label>ایمیل ورود مدیر</label>
                        <input type="email" name="admin_email" value="<?= h($_POST['admin_email'] ?? '') ?>" required dir="ltr" style="text-align:left;">
                    </div>
                    <div>
                        <label>رمز عبور مدیر</label>
                        <input type="password" name="admin_password" required minlength="6">
                    </div>
                </div>
                <div class="alert ok" style="background:#fff7ed;color:#9a3412;border-color:#fdba74;">
                    <label style="display:flex;gap:8px;align-items:flex-start;margin:0;cursor:pointer;">
                        <input type="hidden" name="wipe_database" value="0">
                        <input type="checkbox" name="wipe_database" value="1" <?= (! isset($_POST['wipe_database']) || (string) ($_POST['wipe_database'] ?? '1') === '1') ? 'checked' : '' ?> style="width:auto;margin-top:3px;">
                        <span>
                            اگر از نصب قبلی جدول در دیتابیس مانده، جداول را پاک کن و از نو نصب کن
                            <div style="font-size:11px;color:#7c2d12;margin-top:4px;">برای نصب مجدد روی همان دیتابیس لازم است (خطای users already exists را رفع می‌کند).</div>
                        </span>
                    </label>
                </div>
                <button class="btn" type="submit">نصب نهایی با برند شرکت</button>
            </form>

        <?php else: ?>
            <h2>نصب تمام شد</h2>
            <?php if (! empty($state['done']) || $installer->isInstalled()): ?>
                <div class="alert ok">سیستم آماده است.</div>
                <?php $lic = $state['license'] ?? []; ?>
                <?php if (! empty($lic['plan']) || ! empty($lic['plan_months']) || ! empty($lic['expires_at'])): ?>
                    <div class="alert ok" style="background:#eef6ff;color:#1d4f91;border-color:#bfdbfe;">
                        لایسنس:
                        <?php if (! empty($lic['plan'])): ?><strong><?= h((string) $lic['plan']) ?></strong><?php endif; ?>
                        <?php if (! empty($lic['plan_months'])): ?> (<?= (int) $lic['plan_months'] ?> ماه)<?php endif; ?>
                        <?php if (! empty($lic['expires_at'])): ?> — تا <?= h((string) $lic['expires_at']) ?><?php elseif (empty($lic['plan_months'])): ?> — مادام‌العمر<?php endif; ?>
                    </div>
                <?php endif; ?>
                <p>
                    ورود مدیر:
                    <code dir="ltr"><?= h($state['admin_email'] ?? 'ایمیل مدیر') ?></code>
                </p>
                <p>برای امنیت، بعد از ورود موفق می‌توانید فایل <code>public/install.php</code> را حذف یا تغییرنام دهید.</p>
                <p><a class="btn" href="/login" style="display:inline-block;text-decoration:none;">رفتن به صفحه ورود</a></p>
                <form method="post" style="margin-top:18px;" onsubmit="return confirm('نصب از نو شروع شود؟ (قفل نصب پاک می‌شود)');">
                    <input type="hidden" name="action" value="reset_install">
                    <button type="submit" class="btn" style="background:#9a3412;">نصب مجدد از ابتدا</button>
                </form>
            <?php else: ?>
                <p>هنوز نصب کامل نشده. از مرحله ۱ شروع کنید.</p>
                <p><a href="install.php?step=1">شروع نصب</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <p class="foot">اگر Document Root روی پوشه پروژه است، همین کافی است. اگر روی <code>public</code> است، آدرس نصب: <code>/install.php</code></p>
</div>
</body>
</html>
