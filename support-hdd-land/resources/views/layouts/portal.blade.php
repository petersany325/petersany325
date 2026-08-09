<!DOCTYPE html>
<html lang="fa" dir="rtl" data-calendar="{{ app_calendar_type() }}">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b3340">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-calendar" content="{{ app_calendar_type() }}">
    <title>@yield('title', 'کارتابل مشتری | '.shop_name())</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}?v=hd1">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v=hd1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v=p8">
</head>
<body class="@yield('body_class', 'portal-body')">
@if(session('success') || session('error') || $errors->any())
    <div class="p-flash-wrap">
        @if(session('success'))
            <div class="p-alert ok">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-alert err">{{ session('error') }}</div>
        @endif
        @foreach($errors->all() as $err)
            <div class="p-alert err">{{ $err }}</div>
        @endforeach
    </div>
@endif
@yield('content')

@hasSection('nav')
    @yield('nav')
@elseif(!empty($portalCustomer))
<nav class="p-tabbar" aria-label="منوی مشتری">
    <a href="{{ route('portal.home') }}" class="{{ request()->routeIs('portal.home') ? 'is-on' : '' }}">
        <span>⌂</span><small>میز کار</small>
    </a>
    <a href="{{ route('portal.search') }}" class="{{ request()->routeIs('portal.search') ? 'is-on' : '' }}">
        <span>⌕</span><small>جستجو</small>
    </a>
    <a href="{{ route('portal.tickets', ['status' => 'ready']) }}" class="{{ request()->query('status') === 'ready' ? 'is-on' : '' }}">
        <span>✓</span><small>آماده</small>
    </a>
    <a href="{{ route('portal.approvals') }}" class="{{ request()->routeIs('portal.approvals*') ? 'is-on' : '' }}">
        <span>✔</span><small>تأیید</small>
    </a>
    <a href="{{ route('portal.messages') }}" class="{{ request()->routeIs('portal.messages') ? 'is-on' : '' }}">
        <span>✉</span><small>پیام</small>
    </a>
    <a href="{{ route('portal.pay') }}" class="{{ request()->routeIs('portal.pay') ? 'is-on' : '' }}">
        <span>₿</span><small>پرداخت</small>
    </a>
</nav>
@endif

<script>
(function () {
    var map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
    function digits(v){ return String(v||'').replace(/[۰-۹٠-٩]/g, function(c){ return map[c]||c; }).replace(/\D+/g,'').slice(0,8); }
    function fmt(v){ var d=digits(v); if(d.length<=4) return d; if(d.length<=6) return d.slice(0,4)+'/'+d.slice(4); return d.slice(0,4)+'/'+d.slice(4,6)+'/'+d.slice(6); }
    document.addEventListener('input', function(e){
        if(!e.target || !e.target.classList || !e.target.classList.contains('jalali-date')) return;
        var n=fmt(e.target.value); if(e.target.value!==n){ e.target.value=n; }
    });
})();
</script>
@stack('scripts')
</body>
</html>
