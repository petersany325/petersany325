<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>خطایی رخ داد | فراز نت آمل</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:#070b18;color:#eef2ff;font-family:Tahoma,sans-serif}
    main{max-width:36rem;padding:2rem;text-align:center}
    a{color:#c9d0ea}
  </style>
</head>
<body>
  <main>
    <h1>خطایی رخ داد</h1>
    <p>{{ $message ?? 'صفحه در دسترس نیست. چند لحظه بعد دوباره تلاش کنید.' }}</p>
    <p><a href="{{ url('/') }}">بازگشت به صفحهٔ اصلی</a></p>
  </main>
</body>
</html>
