<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f4c5c">
    <title>سرزمین هارد | انتخاب ورود</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f2a2e;
            --muted: #5a7378;
            --teal: #0f766e;
            --teal-2: #14b8a6;
            --amber: #f59e0b;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: Vazirmatn, Tahoma, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 110% -10%, rgba(20,184,166,.28), transparent 55%),
                radial-gradient(700px 380px at -20% 110%, rgba(245,158,11,.2), transparent 50%),
                linear-gradient(165deg, #0f4c5c 0%, #0f766e 42%, #134e4a 100%);
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
            color: #fff8e7;
            margin-bottom: 22px;
            animation: rise .4s ease-out;
        }
        .gate-logo {
            width: 64px; height: 64px;
            margin: 0 auto 12px;
            border-radius: 18px;
            display: grid; place-items: center;
            font-size: 26px;
            background: linear-gradient(135deg, #ffe7b0, #f59e0b);
            color: #123843;
            box-shadow: 0 12px 28px rgba(0,0,0,.22);
        }
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
            color: rgba(255,248,231,.75);
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
        <div class="gate-logo">▣</div>
        <h1>سرزمین هارد</h1>
        <p>مدیریت تعمیرگاه — نوع ورود را انتخاب کنید</p>
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
