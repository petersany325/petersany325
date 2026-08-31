<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل کارمندان')</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=44">
    <link rel="stylesheet" href="{{ asset('css/admin-nav.css') }}?v=3">
    <style>
      .staff-shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh;background:#f4f6f8}
      .staff-side{background:#12161e;color:#fff;padding:1.1rem .9rem;display:flex;flex-direction:column;gap:.8rem}
      .staff-brand strong{display:block;font-size:1rem}.staff-brand span{opacity:.7;font-size:.8rem}
      .staff-nav a{display:flex;align-items:center;gap:.55rem;padding:.65rem .75rem;border-radius:12px;color:#d1d5db;text-decoration:none;font-weight:600;font-size:.92rem}
      .staff-nav a:hover,.staff-nav a.active{background:#1f2937;color:#fff}
      .staff-main{padding:1.2rem 1.4rem}
      .staff-stat{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:1rem}
      .staff-stat strong{display:block;font-size:1.35rem;margin-top:.25rem}
      .staff-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.75rem;margin:1rem 0}
      .staff-topbar{display:none;align-items:center;justify-content:space-between;gap:.75rem;padding:.65rem .85rem;background:#10141c;color:#fff;position:sticky;top:0;z-index:20}
      .staff-topbar button,.staff-logout{
        border:1px solid #3a4150;background:#1c2230;color:#fff;border-radius:10px;padding:.45rem .75rem;font:inherit;font-weight:800;cursor:pointer
      }
      .staff-logout:hover{background:#e23d12;border-color:#e23d12}
      @media(max-width:900px){
        .staff-topbar{display:flex}
        .staff-shell{grid-template-columns:1fr}
        .staff-side{display:none}
        .staff-shell.side-open .staff-side{display:flex;position:fixed;top:0;right:0;bottom:0;width:min(300px,88vw);z-index:30}
      }
    </style>
</head>
<body>
@php
  $path = trim(request()->path(), '/');
  $perms = $perms ?? [];
  $items = class_exists(\App\Support\PortalNav::class)
    ? \App\Support\PortalNav::staffItems($perms, $path)
    : [];
  $cross = class_exists(\App\Support\PortalNav::class)
    ? \App\Support\PortalNav::crossLinks('staff')
    : [['label' => 'مشاهده سایت', 'url' => '/']];
@endphp
<div class="staff-topbar">
  <button type="button" id="staffMenuBtn">☰ منو</button>
  <div style="display:flex;align-items:center;gap:.5rem">
    <strong style="font-size:.85rem">{{ auth()->user()->name ?? 'کارمند' }}</strong>
    <form action="{{ url('/logout') }}" method="post">@csrf
      <button class="staff-logout" type="submit">خروج</button>
    </form>
  </div>
</div>
<div class="staff-shell" id="staffShell">
  <aside class="staff-side">
    <div class="staff-brand">
      <strong>پنل کارمندان</strong>
      <span>{{ auth()->user()->name ?? 'کارمند' }}</span>
    </div>
    <nav class="staff-nav">
      @foreach($items as $it)
        @if($it['show'])
          <a href="{{ $it['href'] }}" class="{{ $it['active'] ? 'active' : '' }}">{{ $it['label'] }}</a>
        @endif
      @endforeach
      @foreach($cross as $cl)
        <a href="{{ url($cl['url']) }}" @if(($cl['url'] ?? '') === '/') target="_blank" @endif>{{ $cl['label'] }}</a>
      @endforeach
    </nav>
    <form action="{{ url('/logout') }}" method="post" style="margin-top:auto">@csrf
      <button class="staff-logout" style="width:100%" type="submit">خروج از حساب</button>
    </form>
  </aside>
  <div class="staff-main">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @yield('content')
  </div>
</div>
<script>
(function(){
  var btn=document.getElementById('staffMenuBtn');
  var shell=document.getElementById('staffShell');
  if(btn&&shell){btn.addEventListener('click',function(){shell.classList.toggle('side-open');});}
})();
</script>
</body>
</html>
