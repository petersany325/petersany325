<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'فاکتورها') — HDD Land</title>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--bg:#f4f7f8;--ink:#122029;--brand:#0f6e6a;--line:#d8e2e7;--card:#fff;--muted:#5c6c76;--font:'Vazirmatn',Tahoma,sans-serif}
    *{box-sizing:border-box}body{margin:0;font-family:var(--font);background:linear-gradient(180deg,#e8f3f2,#f4f7f8 40%);color:var(--ink);min-height:100vh}
    .wrap{max-width:720px;margin:0 auto;padding:1rem 1rem 5rem}
    header{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}
    header h1{margin:0;font-size:1.25rem}
    header a{color:var(--brand);text-decoration:none;font-size:.9rem}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:1rem;margin-bottom:.75rem;box-shadow:0 8px 24px rgba(15,28,36,.06)}
    .card strong{display:block}
    .meta{color:var(--muted);font-size:.85rem;margin-top:.25rem}
    .badge{display:inline-flex;padding:.15rem .5rem;border-radius:999px;background:#e8f3f2;color:var(--brand);font-size:.75rem}
    .row{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}
    .price{font-weight:800;white-space:nowrap}
    table{width:100%;border-collapse:collapse;font-size:.9rem}
    th,td{padding:.55rem .3rem;border-bottom:1px solid var(--line);text-align:right}
    .bottom{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--line);display:flex;justify-content:space-around;padding:.55rem;font-size:.8rem}
    .bottom a{color:var(--ink);text-decoration:none;display:grid;place-items:center;gap:.15rem}
    .empty{text-align:center;color:var(--muted);padding:2rem 1rem}
  </style>
</head>
<body>
  <div class="wrap">@yield('content')</div>
  <nav class="bottom">
    <a href="{{ url('/app/account') }}"><span>☺</span>حساب</a>
    <a href="{{ route('account.invoices') }}"><span>▤</span>فاکتور</a>
    <a href="{{ url('/account/orders') }}"><span>▣</span>سفارش</a>
    <a href="{{ url('/app') }}"><span>⌂</span>فروشگاه</a>
  </nav>
</body>
</html>
