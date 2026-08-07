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
            background:
                linear-gradient(180deg, #3a4454 0%, #2b3340 38%, #e8eaee 38%, #e8eaee 100%);
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }
        .gate {
            width: min(520px, 100%);
            position: relative;
        }
        .gate-brand {
            text-align: center;
            color: #f2f4f7;
            margin-bottom: 22px;
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
        }
        .gate-brand p {
            margin: 8px 0 0;
            font-size: 13px;
            opacity: .88;
        }
        .gate-cards {
            display: grid;
            gap: 12px;
            animation: rise .45s ease-out .05s both;
        }
        .gate-card {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 18px 16px;
            border-radius: 20px;
            text-decoration: none;
            color: inherit;
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(255,255,255,.5);
            box-shadow: 0 16px 36px rgba(8,30,36,.22);
            transition: transform .14s ease, box-shadow .14s ease;
        }
        .gate-card:active { transform: scale(.985); }
        .gate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(8,30,36,.28);
        }
        .gate-ico {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: grid; place-items: center;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }
        .gate-card.customer .gate-ico {
            background: linear-gradient(135deg, #14b8a6, #0f766e);
        }
        .gate-card.staff .gate-ico {
            background: linear-gradient(135deg, #38bdf8, #0369a1);
        }
        .gate-text { min-width: 0; flex: 1; }
        .gate-text strong {
            display: block;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .gate-text span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.55;
        }
        .gate-go {
            font-size: 20px;
            color: #94a3b8;
            font-weight: 700;
        }
        .gate-foot {
            text-align: center;
            margin-top: 18px;
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
                <span>پنل مدیریت تعمیرگاه — دسترسی‌ها مطابق نقش و مجوزهایی که ادمین اصلی تعیین کرده</span>
            </span>
            <span class="gate-go">←</span>
        </a>
    </div>

    <div class="gate-foot">support.hdd-land.ir</div>
</div>
</body>
</html>
