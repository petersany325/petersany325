<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'حسابداری') — HDD Land</title>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f2f6f7;--ink:#102029;--muted:#5b6b75;--line:#d5e0e6;--card:#fff;
      --brand:#0f6e6a;--brand2:#0b4f4c;--accent:#c45c26;--ok:#1f7a4c;--warn:#a15c00;--danger:#b42318;
      --font:'Vazirmatn',Tahoma,sans-serif;--shadow:0 10px 28px rgba(16,32,41,.07);
    }
    *{box-sizing:border-box}body{margin:0;font-family:var(--font);background:
      radial-gradient(1000px 420px at 100% -10%,rgba(15,110,106,.12),transparent 55%),
      radial-gradient(800px 360px at 0 0,rgba(196,92,38,.08),transparent 50%),var(--bg);color:var(--ink)}
    .shell{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
    .side{background:linear-gradient(180deg,var(--brand2),#08302e);color:#e7f4f3;padding:1rem .85rem;position:sticky;top:0;height:100vh;overflow:auto}
    .side a{color:inherit;text-decoration:none}
    .brand{display:flex;gap:.65rem;align-items:center;padding:.35rem .45rem 1rem;border-bottom:1px solid rgba(255,255,255,.12);margin-bottom:.75rem}
    .brand .m{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.12);display:grid;place-items:center;font-weight:800}
    .brand strong{display:block;font-size:.95rem}.brand span{display:block;font-size:.72rem;opacity:.75}
    .nav{display:grid;gap:.22rem}.nav .g{margin:.7rem .35rem .3rem;font-size:.68rem;opacity:.65}
    .nav a{display:block;padding:.62rem .7rem;border-radius:12px;font-size:.9rem}
    .nav a:hover,.nav a.on{background:rgba(255,255,255,.12)}
    .main{padding:1rem 1.1rem 2rem}
    .top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:1rem}
    .top h1{margin:0;font-size:1.3rem}.top p{margin:.2rem 0 0;color:var(--muted);font-size:.9rem}
    .actions{display:flex;gap:.45rem;flex-wrap:wrap}
    .btn{appearance:none;border:0;border-radius:12px;padding:.62rem .95rem;background:var(--brand);color:#fff;font:inherit;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
    .btn:hover{background:var(--brand2)}.btn.g{background:#fff;color:var(--ink);border:1px solid var(--line)}.btn.w{background:var(--accent)}.btn.o{background:var(--ok)}
    .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:1rem}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:1rem}
    .card h3{margin:0;font-size:.82rem;color:var(--muted)}.card .v{margin-top:.4rem;font-size:1.2rem;font-weight:800}.card .s{margin-top:.2rem;font-size:.78rem;color:var(--muted)}
    .panel{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:1rem}
    .panel .hd{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.85rem 1rem;border-bottom:1px solid var(--line)}
    .panel .bd{padding:1rem}
    table{width:100%;border-collapse:collapse}th,td{padding:.65rem .55rem;border-bottom:1px solid var(--line);text-align:right;font-size:.88rem;vertical-align:top}
    th{color:var(--muted);background:#f7fafb;font-weight:600}
    .badge{display:inline-flex;padding:.12rem .5rem;border-radius:999px;font-size:.72rem;background:#e8f3f2;color:var(--brand2)}
    .badge.issued,.badge.calculated{background:#e8f7ee;color:var(--ok)}.badge.draft{background:#fff4e5;color:var(--warn)}
    .badge.cancelled,.badge.converted{background:#fdecec;color:var(--danger)}
    .form{display:grid;gap:.75rem}.form .row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}
    .form label{display:grid;gap:.3rem;font-size:.82rem;color:var(--muted)}
    .form input,.form select,.form textarea{width:100%;border:1px solid var(--line);border-radius:12px;padding:.6rem .7rem;font:inherit;background:#fff;color:var(--ink)}
    .flash{padding:.7rem 1rem;border-radius:12px;margin-bottom:.8rem}.flash.ok{background:#e8f7ee;color:var(--ok)}.flash.err{background:#fdecec;color:var(--danger)}
    .chips{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.8rem}
    .chips a{padding:.4rem .7rem;border-radius:999px;background:#eef5f4;color:var(--brand2);text-decoration:none;font-size:.82rem}
    .check{display:flex!important;flex-direction:row!important;align-items:center;gap:.45rem;color:var(--ink)!important}
    .mnav{display:none}
    @media(max-width:960px){
      .shell{grid-template-columns:1fr}.side{position:relative;height:auto;border-radius:0 0 18px 18px}
      .grid{grid-template-columns:repeat(2,minmax(0,1fr))}.form .row{grid-template-columns:1fr}
      .mnav{display:flex;gap:.35rem;overflow:auto;padding:0 0 .8rem}.mnav a{flex:0 0 auto;padding:.5rem .75rem;border-radius:999px;background:#fff;border:1px solid var(--line);text-decoration:none;color:var(--ink);font-size:.82rem}
    }
    @media(max-width:560px){.grid{grid-template-columns:1fr}.main{padding:.8rem}table{display:block;overflow:auto}}
  </style>
</head>
<body>
@php $portal = $portal ?? 'admin'; @endphp
<div class="shell">
  <aside class="side">
    <a class="brand" href="{{ $portal==='staff' ? route('staff.accounting.hub') : route('admin.accounting.hub') }}">
      <div class="m">◈</div>
      <div><strong>حسابداری HDD Land</strong><span>{{ $portal==='staff' ? 'پنل کارمند' : 'مدیریت مالی' }}</span></div>
    </a>
    <nav class="nav">
      @if($portal==='admin')
        <div class="g">عملیات</div>
        <a class="{{ request()->routeIs('admin.accounting.hub')?'on':'' }}" href="{{ route('admin.accounting.hub') }}">میز کار</a>
        <a class="{{ request()->routeIs('admin.accounting.docs*')||request()->routeIs('admin.accounting.doc')?'on':'' }}" href="{{ route('admin.accounting.docs') }}">اسناد مالی</a>
        <a href="{{ route('admin.accounting.docs.create',['type'=>'sale']) }}">فاکتور فروش + سریال</a>
        <a href="{{ route('admin.accounting.docs.create',['type'=>'purchase']) }}">فاکتور خرید + سریال</a>
        <a href="{{ route('admin.accounting.docs.create',['type'=>'proforma']) }}">پیش‌فاکتور</a>
        <a href="{{ route('admin.accounting.docs.create',['type'=>'voucher']) }}">سند دستی</a>
        <div class="g">انبار و بانک</div>
        <a class="{{ request()->routeIs('admin.accounting.warehouses')?'on':'' }}" href="{{ route('admin.accounting.warehouses') }}">تعریف انبار چندگانه</a>
        <a class="{{ request()->routeIs('admin.accounting.stock')?'on':'' }}" href="{{ route('admin.accounting.stock') }}">رسید / حواله / انتقال</a>
        <a class="{{ request()->routeIs('admin.accounting.banks')?'on':'' }}" href="{{ route('admin.accounting.banks') }}">تعریف بانک</a>
        <div class="g">هزینه و پرسنل</div>
        <a class="{{ request()->routeIs('admin.accounting.expenses')?'on':'' }}" href="{{ route('admin.accounting.expenses') }}">هزینه‌ها</a>
        <a class="{{ request()->routeIs('admin.accounting.payroll*')?'on':'' }}" href="{{ route('admin.accounting.payroll') }}">حقوق و دستمزد</a>
        <a class="{{ request()->routeIs('admin.accounting.commissions')?'on':'' }}" href="{{ route('admin.accounting.commissions') }}">درصد فروشندگان</a>
        <div class="g">چک و اقساط</div>
        <a class="{{ request()->routeIs('admin.accounting.checks*')?'on':'' }}" href="{{ route('admin.accounting.checks') }}">چک‌ها</a>
        <a class="{{ request()->routeIs('admin.accounting.installments*')?'on':'' }}" href="{{ route('admin.accounting.installments') }}">اقساط مشتریان</a>
        <div class="g">تنظیمات و گزارش</div>
        <a class="{{ request()->routeIs('admin.accounting.settings*')?'on':'' }}" href="{{ route('admin.accounting.settings') }}">تنظیمات (دسته/حساب)</a>
        <a class="{{ request()->routeIs('admin.accounting.reports')||request()->routeIs('admin.accounting.reports.*')?'on':'' }}" href="{{ route('admin.accounting.reports') }}">مرکز گزارش‌ها</a>
        <a href="{{ route('admin.accounting.reports.sales') }}">گزارش فروش/خرید</a>
        <a href="{{ route('admin.accounting.reports.staff') }}">گزارش کارمندان</a>
        <a href="{{ route('admin.accounting.reports.warehouse') }}">گزارش انبار</a>
        <a href="{{ route('admin.accounting.reports.checks') }}">گزارش چک</a>
        <a href="{{ url('/admin') }}">بازگشت ادمین</a>
      @else
        <div class="g">کارمند</div>
        <a class="{{ request()->routeIs('staff.accounting.hub')?'on':'' }}" href="{{ route('staff.accounting.hub') }}">میز کار</a>
        <a href="{{ route('staff.accounting.docs') }}">اسناد</a>
        <a href="{{ route('staff.accounting.stock') }}">انبار</a>
        <a href="{{ route('staff.accounting.reports') }}">گزارش</a>
        <a href="{{ url('/staff') }}">پنل کارمند</a>
      @endif
    </nav>
  </aside>
  <main class="main">
    <div class="mnav">
      @if($portal==='admin')
        <a href="{{ route('admin.accounting.hub') }}">میز</a>
        <a href="{{ route('admin.accounting.docs') }}">اسناد</a>
        <a href="{{ route('admin.accounting.stock') }}">انبار</a>
        <a href="{{ route('admin.accounting.checks') }}">چک</a>
        <a href="{{ route('admin.accounting.installments') }}">اقساط</a>
        <a href="{{ route('admin.accounting.reports') }}">گزارش</a>
      @else
        <a href="{{ route('staff.accounting.hub') }}">میز</a>
        <a href="{{ route('staff.accounting.docs') }}">اسناد</a>
        <a href="{{ route('staff.accounting.stock') }}">انبار</a>
        <a href="{{ route('staff.accounting.reports') }}">گزارش</a>
      @endif
    </div>
    @if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @yield('content')
  </main>
</div>
@stack('scripts')
</body>
</html>
