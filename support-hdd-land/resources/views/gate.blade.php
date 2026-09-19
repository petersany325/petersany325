<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b3340">
    <title>سرزمین هارد | انتخاب ورود</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v=hd1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1f2933;
            --muted: #5f6b7a;
            --teal: #3a4454;
            --teal-2: #5b6576;
            --amber: #c8922a;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: Vazirmatn, Tahoma, sans-serif;
            color: var(--ink);
            background: #e8eaee;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }
        .gate {
            width: min(520px, 100%);
            position: relative;
            background: #fff;
            border: 1px solid #9aa5b5;
            border-radius: 4px;
            box-shadow: 0 10px 28px rgba(0,0,0,.14);
            overflow: hidden;
        }
        .gate-brand {
            text-align: center;
            color: #f2f4f7;
            margin-bottom: 0;
            padding: 22px 16px 18px;
            background: linear-gradient(180deg, #3a4454, #2b3340);
            animation: rise .4s ease-out;
        }
        .gate-logo {
            width: 88px; height: 88px;
            margin: 0 auto 12px;
            border-radius: 8px;
            display: grid; place-items: center;
            background: #fff;
            border: 1px solid #c5ccd6;
            box-shadow: 0 8px 20px rgba(0,0,0,.18);
            overflow: hidden;
            padding: 8px;
        }
        .gate-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .gate-brand h1 {
            margin: 0;
            font-size: clamp(22px, 5vw, 28px);
            font-weight: 800;
            color: #fff;
        }
        .gate-brand p {
            margin: 8px 0 0;
            font-size: 13px;
            opacity: .88;
            color: #d7dde6;
        }
        .gate-cards {
            display: grid;
            gap: 12px;
            padding: 16px;
            animation: rise .45s ease-out .05s both;
        }
        .gate-card {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 14px 12px;
            border-radius: 3px;
            text-decoration: none;
            color: inherit;
            background: linear-gradient(180deg, #fff, #eef1f5);
            border: 1px solid #9aa5b5;
            box-shadow: none;
            transition: border-color .12s ease, background .12s ease;
        }
        .gate-card:active { transform: none; }
        .gate-card:hover {
            transform: none;
            border-color: #2f6fed;
            background: #f3f7ff;
            box-shadow: none;
        }
        .gate-ico {
            width: 42px; height: 42px;
            border-radius: 3px;
            display: grid; place-items: center;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }
        .gate-card.customer .gate-ico {
            background: #3a4454;
        }
        .gate-card.staff .gate-ico {
            background: #2f6fed;
        }
        .gate-card.intern .gate-ico {
            background: #6d28d9;
        }
        .gate-text { min-width: 0; flex: 1; }
        .gate-text strong {
            display: block;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #1f2933;
        }
        .gate-text span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.55;
        }
        .gate-go {
            font-size: 18px;
            color: #94a3b8;
            font-weight: 700;
        }
        .gate-foot {
            text-align: center;
            margin: 0;
            padding: 0 16px 14px;
            color: #5f6b7a;
            font-size: 11px;
            animation: rise .5s ease-out .1s both;
        }
        @keyframes rise {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (min-width: 640px) {
            .gate-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="gate">
    <div class="gate-brand">
        <div class="gate-logo"><img src="{{ asset('images/logo.png') }}?v=hd1" alt="HDD LAND"></div>
        <h1>سرزمین هارد</h1>
        <p>سیستم مدیریت تعمیرات — نوع ورود را انتخاب کنید</p>
    </div>

    <div class="gate-cards">
        <a class="gate-card customer" href="{{ route('portal.login') }}">
            <span class="gate-ico">م</span>
            <span class="gate-text">
                <strong>ورود مشتری</strong>
                <span>وب‌سرویس اختصاصی کارتابل مشتری — موبایل و تأیید پیامک، پیگیری قبض و پرداخت</span>
            </span>
            <span class="gate-go">←</span>
        </a>

        <a class="gate-card staff" href="{{ route('login') }}">
            <span class="gate-ico">ک</span>
            <span class="gate-text">
                <strong>ورود کارمندان</strong>
                <span id="staff-gate-hint">وب‌سرویس موبایل و پنل کامپیوتر — منوها خودکار با تشخیص دستگاه تنظیم می‌شوند</span>
            </span>
            <span class="gate-go">←</span>
        </a>

        <a class="gate-card intern" href="{{ route('login', ['intern' => 1]) }}">
            <span class="gate-ico">آ</span>
            <span class="gate-text">
                <strong>ورود کارآموز</strong>
                <span>پرتال کارآموز — دفتر روز و خدمات تعریف‌شده شرکت (با دسترسی مدیر)</span>
            </span>
            <span class="gate-go">←</span>
        </a>
    </div>

    <div class="gate-foot">support.hdd-land.ir · تشخیص خودکار موبایل / کامپیوتر</div>
</div>
<script>
(function () {
    try {
        var forced = localStorage.getItem('staff_ui_mode');
        var mode = (forced === 'mobile' || forced === 'desktop')
            ? forced
            : (window.matchMedia('(max-width: 900px)').matches ? 'mobile' : 'desktop');
        var hint = document.getElementById('staff-gate-hint');
        if (hint) {
            hint.textContent = mode === 'mobile'
                ? 'موبایل تشخیص داده شد — ورود پیامکی و منوی لمسی کارمند'
                : 'کامپیوتر تشخیص داده شد — منوی کامل ویندوزی کارمند';
        }
    } catch (e) {}
})();
</script>
</body>
</html>
