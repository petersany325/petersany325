<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2b3340">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'کارتابل مشتری | سرزمین هارد')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}?v=hd1">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v=hd1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v=p4">
</head>
<body class="@yield('body_class', 'portal-body')">
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

@stack('scripts')
</body>
</html>
