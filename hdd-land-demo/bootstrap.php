<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

require_once __DIR__ . '/lib/Store.php';

use Demo\Store;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name']);
    session_start();
}

$store = new Store($config['db']);

function cfg(string $key, $default = null)
{
    global $config;
    return $config[$key] ?? $default;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int $n): string
{
    return number_format($n) . ' تومان';
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['_flash'] = $msg;
        return null;
    }
    $m = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $m;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (! hash_equals(csrf_token(), (string) $t)) {
        http_response_code(419);
        exit('CSRF');
    }
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_staff(): array
{
    $u = user();
    if (! $u || ($u['kind'] ?? '') !== 'staff') {
        header('Location: /login/staff');
        exit;
    }
    return $u;
}

function require_customer(): array
{
    $u = user();
    if (! $u || ($u['kind'] ?? '') !== 'customer') {
        header('Location: /login/customer');
        exit;
    }
    return $u;
}

function path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return rtrim($uri, '/') ?: '/';
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** @return list<array{key:string,label:string,href:string,children?:list}> */
function staff_menu(): array
{
    return [
        ['key' => 'home', 'label' => 'میز کار', 'href' => '/s/desk'],
        ['key' => 'reception', 'label' => 'پذیرش', 'href' => '/s/reception', 'children' => [
            ['label' => 'پذیرش جدید / لیست', 'href' => '/s/reception'],
            ['label' => 'تحویل گروهی', 'href' => '/s/delivery'],
        ]],
        ['key' => 'handoffs', 'label' => 'ارجاع', 'href' => '/s/referral'],
        ['key' => 'notifications', 'label' => 'اعلان‌ها', 'href' => '/s/notifications'],
        ['key' => 'daily', 'label' => 'دفتر روز', 'href' => '/s/daily-logs'],
        ['key' => 'cost', 'label' => 'تأیید هزینه', 'href' => '/s/cost'],
        ['key' => 'customers', 'label' => 'مشتریان', 'href' => '/s/customers'],
        ['key' => 'parts', 'label' => 'انبار', 'href' => '/s/parts'],
        ['key' => 'employees', 'label' => 'کارمندان', 'href' => '/s/employees', 'children' => [
            ['label' => 'کارتابل کارمند', 'href' => '/s/employees'],
            ['label' => 'کارتابل کارآموز', 'href' => '/s/interns'],
            ['label' => 'تعمیرکاران', 'href' => '/s/technicians'],
        ]],
        ['key' => 'sms', 'label' => 'پیامک‌ها', 'href' => '/s/sms'],
        ['key' => 'accounting', 'label' => 'حسابداری', 'href' => '/s/accounting'],
        ['key' => 'reports', 'label' => 'گزارش‌ها', 'href' => '/s/reports'],
        ['key' => 'tools', 'label' => 'ابزارها', 'href' => '/s/tools'],
        ['key' => 'settings', 'label' => 'تنظیمات', 'href' => '/s/settings'],
        ['key' => 'portal', 'label' => 'کارتابل مشتری', 'href' => '/s/customer-preview'],
        ['key' => 'store', 'label' => 'فروشگاه', 'href' => '/s/online-store'],
        ['key' => 'corp', 'label' => 'شرکتی', 'href' => '/s/corporate'],
        ['key' => 'book', 'label' => 'نوبت', 'href' => '/s/booking'],
    ];
}
