<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>لینک تأیید نامعتبر | سرزمین هارد</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@600;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100dvh;display:grid;place-items:center;font-family:Vazirmatn,Tahoma,sans-serif;background:#e8eaee;padding:20px;color:#1f2933}
        .card{max-width:420px;width:100%;background:#fff;border:1px solid #c9d0da;border-radius:14px;padding:24px;text-align:center}
        h1{margin:0 0 10px;font-size:20px}
        p{margin:0;color:#5f6b7a;line-height:1.8}
        a{display:inline-block;margin-top:16px;background:#2b3340;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700}
    </style>
</head>
<body>
@php
    $msg = match($reason ?? 'not_found') {
        'expired' => 'این لینک منقضی شده است. از تعمیرگاه بخواهید لینک جدید برایتان بفرستند.',
        'superseded' => 'این لینک با نسخه جدیدتر جایگزین شده و دیگر معتبر نیست.',
        default => 'لینک تأیید پیدا نشد یا نامعتبر است.',
    };
@endphp
<div class="card">
    <h1>لینک قابل استفاده نیست</h1>
    <p>{{ $msg }}</p>
    <a href="{{ url('/cartable') }}">ورود به کارتابل مشتری</a>
</div>
</body>
</html>
