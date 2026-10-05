<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نتیجه حضور | {{ shop_name() }}</title>
    <style>
        body{font-family:Tahoma,Arial,sans-serif;background:#f4f6f8;margin:0;padding:16px}
        .card{max-width:420px;margin:40px auto;background:#fff;border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center}
        .ok{color:#065f46}.err{color:#991b1b}
        a{color:#1d4ed8;text-decoration:none}
    </style>
</head>
<body>
<div class="card">
    <p class="{{ !empty($error) ? 'err' : 'ok' }}">{{ $message }}</p>
    @if(!empty($employee))
        <p style="color:#6b7280;font-size:13px;">{{ $employee->name }}</p>
    @endif
    <p style="margin-top:18px;"><a href="{{ route('login') }}">ورود به پنل</a></p>
</div>
</body>
</html>
