<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $mode === 'approved' ? 'هزینه تأیید شد' : 'هزینه رد شد' }} | سرزمین هارد</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@600;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100dvh;display:grid;place-items:center;font-family:Vazirmatn,Tahoma,sans-serif;background:#e8eaee;padding:20px;color:#1f2933}
        .card{max-width:420px;width:100%;background:#fff;border:1px solid #c9d0da;border-radius:14px;padding:24px;text-align:center;box-shadow:0 10px 28px rgba(31,41,51,.08)}
        .code{font-size:13px;color:#5f6b7a;margin-top:8px}
        h1{margin:0 0 8px;font-size:22px}
        p{margin:0;color:#5f6b7a;line-height:1.8}
        .amt{font-size:26px;font-weight:800;margin:14px 0}
        .ok{color:#1f7a4d} .bad{color:#9b2c2c}
    </style>
</head>
<body>
<div class="card">
    @if($mode === 'approved')
        <h1 class="ok">هزینه تأیید شد</h1>
        <p>رسید تأیید برای قبض {{ $approval->reception?->ticket_no ?: '—' }} ثبت شد.</p>
    @else
        <h1 class="bad">هزینه رد شد</h1>
        <p>رد شما برای قبض {{ $approval->reception?->ticket_no ?: '—' }} ثبت شد.</p>
    @endif
    <div class="amt">{{ number_format((int) $approval->amount) }} تومان</div>
    <div class="code">کد پیگیری: <b dir="ltr">{{ $approval->approval_code }}</b></div>
    <div class="code">زمان: {{ $approval->decided_at?->format('Y/m/d H:i') ?: '—' }}</div>
</div>
</body>
</html>
