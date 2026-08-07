<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b3340">
    <meta name="robots" content="noindex,nofollow">
    <title>تأیید هزینه | سرزمین هارد</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:#1f2933; --muted:#5f6b7a; --line:#c9d0da; --bg:#e8eaee;
            --panel:#fff; --brand:#2b3340; --ok:#1f7a4d; --warn:#8a5a12; --danger:#9b2c2c;
        }
        * { box-sizing:border-box; }
        body {
            margin:0; min-height:100dvh; font-family:Vazirmatn,Tahoma,sans-serif; color:var(--ink);
            background:
                radial-gradient(1200px 500px at 100% -10%, rgba(200,146,42,.18), transparent 55%),
                radial-gradient(900px 420px at -10% 110%, rgba(43,51,64,.12), transparent 50%),
                var(--bg);
            padding:20px 14px 40px;
        }
        .wrap { max-width:440px; margin:0 auto; }
        .brand {
            display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:16px;
        }
        .brand img { height:34px; width:auto; }
        .brand span { font-weight:800; font-size:14px; color:var(--brand); }
        .card {
            background:var(--panel); border:1px solid var(--line); border-radius:14px;
            box-shadow:0 10px 28px rgba(31,41,51,.08); overflow:hidden;
        }
        .head {
            background:linear-gradient(135deg,#2b3340,#3f4a5a); color:#fff; padding:18px 18px 16px;
        }
        .head .kicker { opacity:.8; font-size:12px; margin-bottom:4px; }
        .head h1 { margin:0; font-size:20px; font-weight:800; }
        .head .ticket { margin-top:8px; font-size:12px; opacity:.9; }
        .body { padding:16px 18px 18px; }
            .amount {
            text-align:center; padding:14px 10px;
            border:1px dashed #d5dbe3; border-radius:12px; margin-bottom:14px; background:#f7f8fa;
        }
        .amount small { display:block; color:var(--muted); font-size:12px; margin-bottom:4px; }
        .amount strong { font-size:28px; font-weight:800; letter-spacing:-.02em; }
        .amount strong span { font-size:13px; font-weight:700; color:var(--muted); margin-right:4px; }
        .kv { display:grid; gap:8px; margin-bottom:14px; }
        .kv div { display:flex; justify-content:space-between; gap:10px; font-size:13px; border-bottom:1px solid #eef1f4; padding-bottom:7px; }
        .kv span { color:var(--muted); }
        .kv strong { font-weight:700; text-align:left; }
        .box {
            background:#f7f8fa; border:1px solid #e3e8ef; border-radius:10px; padding:12px;
            font-size:12.5px; line-height:1.8; color:#334155; margin-bottom:12px; white-space:pre-wrap;
        }
        .terms {
            font-size:12px; line-height:1.75; color:var(--muted); background:#fff8eb;
            border:1px solid #efd7a4; border-radius:10px; padding:11px 12px; margin-bottom:12px; white-space:pre-wrap;
        }
        .check { display:flex; gap:8px; align-items:flex-start; font-size:12.5px; margin:0 0 14px; }
        .check input { margin-top:3px; }
        .actions { display:grid; gap:8px; }
        .btn {
            appearance:none; border:0; border-radius:10px; padding:12px 14px; font:inherit; font-weight:800;
            cursor:pointer; text-align:center; width:100%;
        }
        .btn-ok { background:var(--ok); color:#fff; }
        .btn-danger { background:transparent; color:var(--danger); border:1px solid #e7b4b4; }
        .btn-ghost { background:#eef1f4; color:var(--brand); }
        .alert { background:#fde8e8; color:var(--danger); border:1px solid #f0c2c2; border-radius:10px; padding:10px 12px; margin-bottom:12px; font-size:12.5px; }
        .okbanner { background:#e7f6ee; color:var(--ok); border:1px solid #bfe5cd; border-radius:10px; padding:10px 12px; margin-bottom:12px; font-size:12.5px; }
        .meta { text-align:center; color:var(--muted); font-size:11.5px; margin-top:14px; line-height:1.7; }
        details { margin-top:8px; }
        details summary { cursor:pointer; color:var(--muted); font-size:12.5px; font-weight:700; }
        textarea {
            width:100%; min-height:72px; margin:8px 0; border:1px solid var(--line); border-radius:8px;
            padding:10px; font:inherit; resize:vertical; background:#fff;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <img src="{{ asset('images/logo-header.png') }}?v=hd1" alt="HDD LAND">
        <span>سرزمین هارد</span>
    </div>

    <div class="card">
        <div class="head">
            <div class="kicker">درخواست تأیید هزینه</div>
            <h1>{{ $reception?->product_name ?: 'دستگاه تعمیری' }}</h1>
            <div class="ticket">
                قبض {{ $reception?->ticket_no ?: '—' }}
                @if($reception?->serial_number)
                    · سریال <span dir="ltr">{{ $reception->serial_number }}</span>
                @endif
                · نسخه {{ $approval->version }}
            </div>
        </div>
        <div class="body">
            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="okbanner">{{ session('success') }}</div>
            @endif

            <div class="amount">
                <small>مبلغ پیشنهادی برای تأیید</small>
                <strong>{{ number_format((int) $approval->amount) }}<span>تومان</span></strong>
            </div>

            <div class="kv">
                <div><span>مشتری</span><strong>{{ $approval->snapshot['customer_name'] ?? $approval->customer?->name ?: '—' }}</strong></div>
                <div><span>اجرت</span><strong>{{ number_format((int) $approval->labor_cost) }}</strong></div>
                <div><span>قطعات</span><strong>{{ number_format((int) $approval->parts_cost) }}</strong></div>
                <div><span>تخفیف</span><strong>{{ number_format((int) $approval->discount) }}</strong></div>
                <div><span>اعتبار لینک تا</span><strong>{{ $approval->expires_at?->format('Y/m/d H:i') ?: '—' }}</strong></div>
            </div>

            <div class="box"><strong>شرح کار:</strong>
{{ $approval->description ?: '—' }}</div>

            <div class="terms">{{ $approval->terms_text }}</div>

            <form method="POST" action="{{ route('approvals.approve', $token) }}" class="actions">
                @csrf
                <label class="check">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>مبلغ، شرح کار و شرایط بالا را خواندم و تأیید می‌کنم.</span>
                </label>
                <button class="btn btn-ok" type="submit">تأیید هزینه</button>
            </form>

            <details>
                <summary>رد کردن این مبلغ</summary>
                <form method="POST" action="{{ route('approvals.reject', $token) }}">
                    @csrf
                    <textarea name="reject_reason" placeholder="دلیل رد (اختیاری)">{{ old('reject_reason') }}</textarea>
                    <button class="btn btn-danger" type="submit">رد هزینه</button>
                </form>
            </details>

            <div class="meta">
                کد پیگیری: <b dir="ltr">{{ $approval->approval_code }}</b><br>
                فقط باز کردن لینک کافی نیست؛ باید دکمه تأیید را بزنید.
            </div>
        </div>
    </div>
</div>
</body>
</html>
