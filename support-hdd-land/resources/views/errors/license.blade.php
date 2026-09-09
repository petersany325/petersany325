<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فعال‌سازی لایسنس</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Tahoma,sans-serif;background:#eef1f5;color:#1f2933}
        .box{background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:28px;max-width:440px;text-align:center;box-shadow:0 10px 30px rgba(15,23,42,.06)}
        h1{margin:0 0 8px;font-size:20px}
        p{color:#667788;line-height:1.7;margin:0 0 12px}
        a.btn{display:inline-block;margin-top:6px;padding:.65rem 1rem;border-radius:10px;background:#1d4f91;color:#fff;text-decoration:none}
        a.link{color:#1d4f91}
        .reason{font-size:12px;color:#94a3b8;margin-top:10px}
    </style>
</head>
<body>
<div class="box">
    <h1>فعال‌سازی لازم است</h1>
    <p>{{ $message ?? 'لایسنس این نصب معتبر نیست یا منقضی شده است.' }}</p>
    @if(!empty($purchase_url))
        <p><a class="btn" href="{{ $purchase_url }}" rel="noopener">خرید / تمدید لایسنس</a></p>
    @endif
    <p><a class="link" href="/install.php">رفتن به نصب / لایسنس</a></p>
    @if(!empty($reason))
        <div class="reason">کد: {{ $reason }}</div>
    @endif
</div>
</body>
</html>
