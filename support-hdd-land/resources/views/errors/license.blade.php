<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>سرزمین هارد — لایسنس</title>
    <style>
        :root {
            --ink: #132033;
            --muted: #5a6b7d;
            --line: #c9d4e0;
            --brand: #0b3d5c;
            --brand-2: #c45c26;
            --paper: #f3f6f9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Tahoma, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 10% -10%, rgba(196, 92, 38, .14), transparent 55%),
                radial-gradient(800px 380px at 100% 0%, rgba(11, 61, 92, .16), transparent 50%),
                linear-gradient(180deg, #e8eef4 0%, var(--paper) 55%, #eef2f6 100%);
        }
        .wrap {
            width: min(520px, 100%);
            border: 1px solid var(--line);
            background: rgba(255,255,255,.92);
            padding: 28px 26px 24px;
        }
        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--brand);
            margin: 0 0 4px;
        }
        .sub {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 13px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 700;
        }
        p {
            margin: 0 0 12px;
            color: var(--muted);
            line-height: 1.85;
            font-size: 14px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        a.btn {
            display: inline-block;
            text-decoration: none;
            padding: 10px 16px;
            font-size: 13px;
            border: 1px solid transparent;
        }
        a.btn-primary {
            background: var(--brand);
            color: #fff;
        }
        a.btn-secondary {
            background: #fff;
            color: var(--brand);
            border-color: var(--line);
        }
        .foot {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
        }
        .foot strong { color: var(--brand-2); }
    </style>
</head>
<body>
@php
    $purchaseUrl = $purchase_url ?? config('license.purchase_url', 'https://hdd-land.ir');
    $reason = $reason ?? null;
    $title = match ($reason) {
        'domain_mismatch' => 'لایسنس به این دامنه قفل نیست',
        'expired' => 'اعتبار لایسنس تمام شده',
        'inactive' => 'لایسنس غیرفعال است',
        default => 'دسترسی به‌خاطر لایسنس بسته شد',
    };
@endphp
<div class="wrap">
    <p class="brand">سرزمین هارد</p>
    <p class="sub">HDD LAND · نرم‌افزار مدیریت تعمیرگاه</p>
    <h1>{{ $title }}</h1>
    <p>{{ $message ?? 'لایسنس این نصب معتبر نیست.' }}</p>
    @if(($reason ?? '') === 'domain_mismatch')
        <p>اگر سایت را به هاست یا دامنهٔ جدید منتقل کرده‌اید، لایسنس قبلی قفل می‌ماند تا انتقال رسمی یا خرید لایسنس جدید انجام شود.</p>
    @endif
    <div class="actions">
        <a class="btn btn-primary" href="{{ $purchaseUrl }}" rel="noopener" target="_blank">خرید / تمدید لایسنس</a>
        <a class="btn btn-secondary" href="{{ $purchaseUrl }}" rel="noopener" target="_blank">hdd-land.ir</a>
    </div>
    <div class="foot">
        برای انتقال دامنه، تمدید یا خرید لایسنس جدید با <strong>سرزمین هارد</strong> هماهنگ کنید:
        <a href="{{ $purchaseUrl }}" rel="noopener" target="_blank">{{ parse_url($purchaseUrl, PHP_URL_HOST) ?: 'hdd-land.ir' }}</a>
    </div>
</div>
</body>
</html>
