<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لایسنس نامعتبر</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Tahoma,sans-serif;background:#eef1f5;color:#1f2933}
        .box{background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:28px;max-width:420px;text-align:center}
        h1{margin:0 0 8px;font-size:20px}
        p{color:#667788;line-height:1.7}
        a{color:#1d4f91}
    </style>
</head>
<body>
<div class="box">
    <h1>فعال‌سازی لازم است</h1>
    <p>{{ $message ?? 'لایسنس معتبر نیست.' }}</p>
    <p><a href="/install.php">رفتن به نصب / لایسنس</a></p>
</div>
</body>
</html>
