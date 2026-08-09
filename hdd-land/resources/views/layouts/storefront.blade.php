<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
      try { $seoHead = \Plugins\SeoCenter\Plugin::settings(); } catch (\Throwable $e) { $seoHead = []; }
    @endphp
    @if(!empty($seoHead['enabled']))
      <meta name="description" content="{{ $seoHead['description'] ?? '' }}">
      <meta name="robots" content="{{ !empty($seoHead['robots_index']) ? 'index,follow,max-image-preview:large' : 'noindex,nofollow' }}">
      <link rel="canonical" href="{{ url()->current() }}">
      @if(!empty($seoHead['google_verification']))<meta name="google-site-verification" content="{{ $seoHead['google_verification'] }}">@endif
      @if(!empty($seoHead['bing_verification']))<meta name="msvalidate.01" content="{{ $seoHead['bing_verification'] }}">@endif
      <meta property="og:site_name" content="{{ $seoHead['site_title'] ?? config('app.name') }}">
      <meta property="og:url" content="{{ url()->current() }}"><meta property="og:type" content="website">
    @endif
    <title>@yield('title', \App\Models\Setting::getValue('shop_name', config('app.name', 'فروشگاه'))) </title>
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=51">
    <link rel="stylesheet" href="{{ asset('css/mega-menu.css') }}?v=41">
    <link rel="stylesheet" href="{{ asset('css/account.css') }}?v=4">
    @if(\Illuminate\Support\Facades\View::exists('web-app::storefront-head'))
      @include('web-app::storefront-head')
    @endif
</head>
@php
    $shopName = \App\Models\Setting::getValue('shop_name', config('app.name', 'HDD Land Shop'));
    $cartCount = class_exists(\Plugins\CartCheckout\src\Cart::class) ? \Plugins\CartCheckout\src\Cart::count() : 0;
    $mmNavAlign = 'right';
    $headerClass = 'site-header';
    $headerStyle = '';
    $headerBg = '#ffffff';
    try {
        $hdr = \Plugins\MegaMenu\Plugin::headerAppearance();
        $mmNavAlign = $hdr['settings']['nav_align'] ?? 'right';
        $headerClass = $hdr['class'];
        $headerStyle = $hdr['style'];
        $headerBg = $hdr['bg'] ?? '#ffffff';
    } catch (\Throwable $e) {}
    $waBodyClass = '';
    $waQuick = [];
    try {
        if (class_exists(\Plugins\WebApp\Plugin::class)) {
            $waS = \Plugins\WebApp\Plugin::settings();
            if (! empty($waS['enabled']) && ! empty($waS['storefront_bottom_nav'])) {
                $waBodyClass = ' has-wa-tabbar';
            }
            if (! empty($waS['enabled']) && ! empty($waS['show_quick_links'])) {
                foreach ([1, 2, 3] as $i) {
                    $lab = trim((string) ($waS['quick_link_'.$i.'_label'] ?? ''));
                    $href = trim((string) ($waS['quick_link_'.$i.'_url'] ?? ''));
                    if ($lab !== '' && $href !== '') {
                        $waQuick[] = ['label' => $lab, 'href' => $href];
                    }
                }
            }
        }
    } catch (\Throwable $e) {}
@endphp
<body class="{{ request()->boolean('theme_preview') ? 'theme-preview' : '' }}{{ $waBodyClass }}">
<style id="mm-header-live">
  .site-header{background:{{ $headerBg }} !important}
</style>
<div class="topbar">
    <div class="container">
        <span>فروشگاه سخت‌افزار و تجهیزات ذخیره‌سازی</span>
        <span>{{ \App\Models\Setting::getValue('shop_phone', '۰۲۱-۰۰۰۰۰۰۰۰') }}</span>
    </div>
</div>
<header class="{{ $headerClass }}" style="{{ $headerStyle }}">
    <div class="container header-row header-row-mega header-align-{{ $mmNavAlign }}">
        <div class="header-cluster" dir="rtl">
            <button type="button" class="nav-toggle" id="navToggle" aria-label="منو" aria-expanded="false">☰</button>
            <a class="brand" href="{{ route('home') }}">
                <img class="brand-logo brand-logo-official" src="{{ asset('images/hdd-land-logo.png') }}?v=2" width="78" height="48" alt="لوگوی HDD LAND">
                <span>{{ $shopName }}</span>
            </a>
            <div class="header-nav-slot" id="headerNavSlot">
              <div class="header-nav-wrap" id="headerNavWrap">
                <div class="mobile-nav-head">
                  <strong>منوی سایت</strong>
                  <button type="button" class="mobile-nav-close" id="navCloseBtn" aria-label="بستن منو">×</button>
                </div>
                @include('mega-menu::storefront.nav')
              </div>
            </div>
            <div class="header-utils">
              <a class="hdr-util" href="{{ url('/cart') }}">سبد@if($cartCount>0)<i>{{ $cartCount }}</i>@endif</a>
              @auth
                <a class="hdr-util" href="{{ route('account.index') }}">حساب</a>
                <form action="{{ url('/logout') }}" method="post" class="hdr-logout">@csrf
                  <button type="submit">خروج</button>
                </form>
              @else
                <a class="hdr-util" href="{{ route('login') }}">ورود</a>
              @endauth
            </div>
        </div>
    </div>
</header>
<div class="nav-backdrop" id="navBackdrop" hidden></div>

<main class="container">
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:1rem">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error" style="margin-top:1rem">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error" style="margin-top:1rem">
            <ul style="margin:0;padding-right:1.1rem">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>

<footer class="site-footer legacy-footer" hidden>
    <div class="container footer-grid">
        <div>
            <strong>{{ $shopName }}</strong>
            <p class="muted">فروشگاه تخصصی سخت‌افزار با مشخصات فنی کامل و گارانتی شفاف.</p>
        </div>
        <div>
            <strong>دسترسی سریع</strong>
            <div class="stack footer-links" style="margin-top:0.6rem">
                <a href="{{ route('products.index') }}">محصولات</a>
                <a href="{{ url('/app/shop') }}">وب‌اپ فروشگاه</a>
                <a href="{{ url('/serial-check') }}">استعلام گارانتی</a>
                <a href="{{ route('orders.track') }}">پیگیری سفارش</a>
                @foreach($waQuick as $ql)
                  <a href="{{ url($ql['href']) }}">{{ $ql['label'] }}</a>
                @endforeach
                @auth
                  <a href="{{ route('account.index') }}">حساب من</a>
                  <a href="{{ url('/app/account') }}">حساب وب‌اپ</a>
                @else
                  <a href="{{ route('login') }}">ورود مشتریان</a>
                  <a href="{{ route('register') }}">ثبت‌نام</a>
                @endauth
            </div>
        </div>
        <div>
            <strong>ارتباط</strong>
            <p class="muted" style="margin-top:0.6rem">{{ \App\Models\Setting::getValue('shop_address', 'تهران، ایران') }}</p>
            @auth
              <form action="{{ url('/logout') }}" method="post" style="margin-top:.75rem">@csrf
                <button type="submit" class="footer-logout">خروج از حساب</button>
              </form>
            @endauth
        </div>
    </div>
</footer>
@php
    $footerConfigFile = app_path('Support/FooterConfig.php');
    if (!class_exists(\App\Support\FooterConfig::class) && is_file($footerConfigFile)) {
        require_once $footerConfigFile;
    }
@endphp
@include('storefront.footer-modern')
<script src="{{ asset('js/mega-menu.js') }}?v=40" defer></script>
@php
  try {
    $scBase = base_path('plugins/SmartChat');
    \Illuminate\Support\Facades\View::addNamespace('smart-chat', $scBase);
    foreach ([base_path('plugins/Support/JsonSettings.php'), $scBase.'/Plugin.php', $scBase.'/KnowledgeEngine.php'] as $scFile) {
      if (is_file($scFile)) require_once $scFile;
    }
  } catch (\Throwable $e) {}
@endphp
@if(\Illuminate\Support\Facades\View::exists('smart-chat::widget'))
  @include('smart-chat::widget')
@endif
@if(\Illuminate\Support\Facades\View::exists('web-app::storefront-foot'))
  @include('web-app::storefront-foot')
@endif
</body>
</html>
