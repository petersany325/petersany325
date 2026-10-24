@php
  $studioAgency = false;
  $studioHome = [];
  try {
      $studioAgency = class_exists(\App\Support\SiteHost::class)
          && \App\Support\SiteHost::isStudio()
          && class_exists(\App\Support\StudioHomeConfig::class)
          && ! empty(\App\Support\StudioHomeConfig::get()['enabled']);
      if ($studioAgency) {
          $studioHome = \App\Support\StudioHomeConfig::get();
          \Plugins\Studio\Plugin::registerViews();
      }
  } catch (\Throwable $e) {
      $studioAgency = false;
  }
@endphp
@if($studioAgency)
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@if(request()->is('contact')){{ \App\Support\StudioDesignCopy::seoTitle('contact') }}@elseif(request()->is('about')){{ \App\Support\StudioDesignCopy::seoTitle('about') }}@else@yield('title', ($studioHome['tagline'] ?? 'طراحی سایت، نرم‌افزار و هوش مصنوعی').' | فراز نت آمل')@endif</title>
  <meta name="description" content="@if(request()->is('contact')){{ \App\Support\StudioDesignCopy::seoDescription('contact') }}@elseif(request()->is('about')){{ \App\Support\StudioDesignCopy::seoDescription('about') }}@else{{ $studioHome['hero_text'] ?? 'طراحی سایت حرفه‌ای با فراز نت آمل.' }}@endif">
  @if(\App\Support\StudioHomeConfig::mobileWebAppOn($studioHome))
    <link rel="manifest" href="{{ url('/studio-manifest.webmanifest') }}">
    <meta name="theme-color" content="{{ $studioHome['accent'] ?? '#7c5cff' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="فرازنت">
    <link rel="apple-touch-icon" href="{{ asset('images/hdd-land-icon-192.png') }}">
  @endif
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap">
  <link rel="stylesheet" href="{{ asset('css/studio.css') }}?v=17">
  <link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=14">
</head>
<body class="st-body ag-body{{ \App\Support\StudioHomeConfig::mobileWebAppOn($studioHome) ? ' has-ag-tabbar' : '' }}" style="--ag-accent:{{ $studioHome['accent'] ?? '#7c5cff' }};--ag-bg:{{ $studioHome['bg'] ?? '#070b18' }}">
@include('studio::nav', ['studioHome' => $studioHome, 'agency' => true])
<main class="site-main">
  @if(session('success'))<div class="ag-note" style="margin:1rem auto;width:min(1180px,calc(100% - 2.2rem))">{{ session('success') }}</div>@endif
  @if(request()->is('contact'))
    @include('studio::bodies', ['slug' => 'contact', 'studioHome' => $studioHome, 'contactCopy' => \App\Support\StudioDesignCopy::contact()])
  @elseif(request()->is('about'))
    @include('studio::bodies', ['slug' => 'about', 'studioHome' => $studioHome, 'aboutPage' => \App\Support\StudioDesignCopy::about(), 'reasons' => \App\Support\StudioDesignCopy::reasons()])
  @else
    @yield('content')
  @endif
</main>
<footer class="st-foot ag-foot">
  <div class="ag-wrap">
    <span>hdd-land.ir — {{ $studioHome['tagline'] ?? 'تخصصی طراحی سایت و نرم‌افزار' }}</span>
    <span>
      <a href="{{ url('/design') }}">طراحی سایت</a>
      ·
      <a href="{{ url('/about') }}">درباره ما</a>
      ·
      <a href="{{ url('/accounting') }}">حسابداری وب</a>
      ·
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      ·
      <a href="{{ url('/repair-shop') }}">تعمیرگاه</a>
      ·
      <a href="{{ \App\Support\SiteHost::shopUrl('/') }}">فروشگاه قطعات</a>
      ·
      <a href="{{ \App\Support\SiteHost::receiptUrl() }}" rel="noopener">سامانه قبض</a>
    </span>
  </div>
</footer>
@include('studio::mobile', ['studioHome' => $studioHome])
<script>
(function(){
  var btn=document.querySelector('[data-ag-burger]');
  var drawer=document.getElementById('agDrawer');
  if(!btn||!drawer) return;
  btn.addEventListener('click', function(){
    var open=drawer.hidden;
    drawer.hidden=!open;
    btn.setAttribute('aria-expanded', open?'true':'false');
    document.body.classList.toggle('ag-drawer-on', open);
  });
})();
</script>
</body>
</html>
@else
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=68">
    <link rel="stylesheet" href="{{ asset('css/mega-menu.css') }}?v=50">
    <link rel="stylesheet" href="{{ asset('css/home-corporate.css') }}?v=10">
    <link rel="stylesheet" href="{{ asset('css/account.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/webapp.css') }}?v=24">
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
<body id="top" class="{{ request()->boolean('theme_preview') ? 'theme-preview' : '' }}{{ $waBodyClass }}{{ request()->routeIs('home') ? ' hl-home' : '' }}">
<div class="topbar">
    <div class="container">
        <div class="topbar-start">
          <span>تأمین سخت‌افزار ذخیره‌سازی برای سازمان‌ها و حرفه‌ای‌ها</span>
        </div>
        <div class="topbar-end">
          <a href="tel:{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::getValue('shop_phone', '01144447220')) }}">{{ \App\Models\Setting::getValue('shop_phone', '01144447220') }}</a>
          @php
            $topEmail = '';
            try { $topEmail = trim((string) (\App\Support\FooterConfig::get()['email'] ?? '')); } catch (\Throwable $e) {}
            if ($topEmail === '') { $topEmail = trim((string) \App\Models\Setting::getValue('shop_email', '')); }
          @endphp
          @if($topEmail !== '')
            <span class="topbar-dot">·</span>
            <a href="mailto:{{ $topEmail }}">{{ $topEmail }}</a>
          @endif
        </div>
    </div>
</div>
<header class="{{ $headerClass }} hl-header nav-align-{{ $mmNavAlign }}" @if($headerStyle !== '') style="{{ $headerStyle }}" @endif>
    <div class="container hl-headbar" dir="rtl">
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
            <nav class="hl-extra-nav" aria-label="صفحات شرکت">
              <a href="{{ url('/services') }}">خدمات سازمانی</a>
              <a href="{{ url('/training') }}">آموزش</a>
              <a href="{{ url('/about') }}">درباره ما</a>
            </nav>
          </div>
        </div>
        <div class="hl-headbar__utils">
          <a class="hdr-util" href="{{ url('/cart') }}">سبد خرید@if($cartCount>0)<i>{{ $cartCount }}</i>@endif</a>
          @auth
            <a class="hdr-util" href="{{ route('account.index') }}">حساب کاربری</a>
            <form action="{{ url('/logout') }}" method="post" class="hdr-logout">@csrf
              <button type="submit">خروج</button>
            </form>
          @else
            <a class="hdr-util" href="{{ url('/auth/customer') }}">ورود کاربر</a>
            <a class="hdr-util" href="{{ url('/auth/staff') }}">ورود کارمند</a>
          @endauth
        </div>
    </div>
    <div class="hl-searchbar">
      <div class="container">
        <form class="hl-search" action="{{ url('/products') }}" method="get" role="search">
          <input type="search" name="q" placeholder="جستجوی محصول، برند یا مدل..." value="{{ request('q') }}" autocomplete="off">
          <button type="submit" aria-label="جستجو">جستجو</button>
        </form>
      </div>
    </div>
</header>
<div class="nav-backdrop" id="navBackdrop" hidden></div>

<main class="site-main">
    <div class="container">
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
    </div>
    @if(request()->routeIs('home'))
      @yield('content')
    @else
      <div class="container">@yield('content')</div>
    @endif
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
                  <a href="{{ url('/auth/customer') }}">ورود کاربر</a>
                  <a href="{{ url('/auth/customer?tab=register') }}">ثبت‌نام</a>
                  <a href="{{ url('/auth/staff') }}">ورود کارمند</a>
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
<script src="{{ asset('js/mega-menu.js') }}?v=43" defer></script>
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
@endif
