<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8">
  
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="{{ $s['theme_color'] ?? '#e23d12' }}">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="{{ $s['short_name'] ?? 'سرزمین‌هارد' }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
  @php $iconSrc = \Plugins\WebApp\Plugin::iconUrl($s['icon_192'] ?? null); @endphp
  <link rel="apple-touch-icon" href="{{ $iconSrc }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ $iconSrc }}">
  <title>{{ $title ?? ($s['app_name'] ?? 'سرزمین هارد') }}</title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/webapp.css') }}?v=21">
</head>
@php
  $anim = !empty($s['animations']);
  $compact = !empty($s['compact_cards']);
  $siteMenu = $siteMenu ?? [];
  $quickLinks = $quickLinks ?? [];
  $waFooter = $waFooter ?? null;
  $drawerMenu = $drawerMenu ?? [];
  $drawerOn = !empty($s['drawer_menu_enabled']) && !empty($drawerMenu);
  $drawerSide = (($s['drawer_side'] ?? 'right') === 'left') ? 'left' : 'right';
  $tab = $tab ?? 'home';
  $drawerIcons = [
    'home' => '⌂',
    'shop' => '▣',
    'cart' => '▢',
    'account' => '☺',
    'track' => '☰',
    'warranty' => '⛨',
    'support' => '✉',
    'contact' => '☎',
  ];
@endphp
<body class="wa-body {{ $anim ? 'wa-anim' : '' }} {{ $compact ? 'wa-compact' : '' }} {{ $drawerOn ? 'has-wa-drawer' : '' }}"
  style="--wa-brand:{{ $s['theme_color'] ?? '#e23d12' }};--wa-bg:{{ $s['background_color'] ?? '#f4f6f9' }};--wa-surface:{{ $s['surface_color'] ?? '#ffffff' }};--wa-ink:{{ $s['text_color'] ?? '#1a1d23' }}">

<header class="wa-top">
  <div class="wa-top-start">
    @if($drawerOn)
      <button type="button" class="wa-menu-btn" id="waMenuOpen" aria-label="باز کردن منو" aria-controls="waDrawer" aria-expanded="false">
        <span aria-hidden="true"></span>
      </button>
    @endif
    <a class="wa-brand" href="{{ url('/app') }}">
      <img class="wa-logo" src="{{ $iconSrc }}?v=10" width="40" height="40" alt="{{ $s['app_name'] ?? 'سرزمین هارد' }}" onerror="this.onerror=null;this.src='{{ asset('images/hdd-land-icon-192.png') }}'">
      <strong>{{ $s['app_name'] ?? 'سرزمین هارد' }}</strong>
    </a>
  </div>
  <div class="wa-top-actions">
    @if(!empty($s['show_search']) && ($tab ?? '') !== 'shop')
      <a class="wa-icon-btn" href="{{ url('/app/shop') }}" aria-label="جستجو">⌕</a>
    @endif
    <a class="wa-cart-btn" href="{{ url('/app/cart') }}" aria-label="سبد">
      <span>سبد</span>
      @if(($cartCount ?? 0) > 0)<i class="wa-badge">{{ $cartCount }}</i>@endif
    </a>
  </div>
</header>

@if($drawerOn)
<div class="wa-drawer-backdrop" id="waDrawerBackdrop" hidden></div>
<aside class="wa-drawer wa-drawer-{{ $drawerSide }}" id="waDrawer" data-side="{{ $drawerSide }}" aria-hidden="true" aria-label="{{ $s['drawer_title'] ?? 'منوی وب‌اپ' }}">
  <div class="wa-drawer-head">
    @if(!empty($s['drawer_show_brand']))
      <div class="wa-drawer-brand">
        <img src="{{ $iconSrc }}?v=10" width="36" height="36" alt="" onerror="this.style.display='none'">
        <div>
          <strong>{{ $s['app_name'] ?? 'سرزمین هارد' }}</strong>
          <small>{{ $s['drawer_subtitle'] ?? $s['drawer_title'] ?? 'فروشگاه و تأمین سازمانی' }}</small>
        </div>
      </div>
    @else
      <div class="wa-drawer-brand">
        <div>
          <strong>{{ $s['drawer_title'] ?? 'منوی وب‌اپ' }}</strong>
        </div>
      </div>
    @endif
    <button type="button" class="wa-drawer-close" id="waMenuClose" aria-label="بستن منو">×</button>
  </div>
  <nav class="wa-drawer-nav">
    @foreach($drawerMenu as $item)
      @php
        $href = str_starts_with($item['url'], 'http') ? $item['url'] : url($item['url']);
        $kids = collect($item['children'] ?? [])->filter(fn ($c) => !empty($c['label']) && !empty($c['url']))->values();
        $hasKids = $kids->isNotEmpty();
        $key = (string) ($item['key'] ?? '');
        $ico = trim((string) ($item['icon'] ?? '')) ?: ($drawerIcons[$key] ?? '•');
        $active = false;
        if ($key === 'home' && ($tab ?? '') === 'home') $active = true;
        if ($key === 'shop' && ($tab ?? '') === 'shop') $active = true;
        if ($key === 'cart' && ($tab ?? '') === 'cart') $active = true;
        if ($key === 'account' && ($tab ?? '') === 'account') $active = true;
        $openByDefault = $active && $hasKids;
      @endphp
      @if($hasKids)
        <div class="wa-drawer-group {{ $openByDefault ? 'is-open' : '' }}" data-wa-sub>
          <button type="button" class="wa-drawer-parent {{ $active ? 'active' : '' }}" data-wa-sub-toggle aria-expanded="{{ $openByDefault ? 'true' : 'false' }}">
            <i class="wa-drawer-ico" aria-hidden="true">{{ $ico }}</i>
            <span>{{ $item['label'] }}</span>
            <em class="wa-drawer-caret" aria-hidden="true">▾</em>
          </button>
          <div class="wa-drawer-sub" @if(!$openByDefault) hidden @endif>
            <a href="{{ $href }}" class="wa-drawer-sub-all">{{ $key === 'warranty' ? 'استعلام گارانتی' : 'همهٔ '.$item['label'] }}</a>
            @php $shownCompanyHead = false; @endphp
            @foreach($kids as $child)
              @php
                $ch = str_starts_with($child['url'], 'http') || str_starts_with($child['url'], 'tel:')
                  ? $child['url']
                  : url($child['url']);
                $kind = (string) ($child['kind'] ?? '');
              @endphp
              @if($kind === 'company' && !$shownCompanyHead)
                @php $shownCompanyHead = true; @endphp
                <span class="wa-drawer-sub-label">شرکت‌های گارانتی</span>
              @endif
              <a href="{{ $ch }}" class="{{ $kind === 'company' ? 'wa-drawer-sub-company' : '' }}">{{ $child['label'] }}</a>
            @endforeach
          </div>
        </div>
      @else
        <a href="{{ $href }}" class="{{ $active ? 'active' : '' }}">
          <i class="wa-drawer-ico" aria-hidden="true">{{ $ico }}</i>
          <span>{{ $item['label'] }}</span>
          @if($key === 'cart' && ($cartCount ?? 0) > 0)
            <b class="wa-drawer-badge">{{ $cartCount }}</b>
          @endif
        </a>
      @endif
    @endforeach
  </nav>
  @if(!empty($s['drawer_show_full_site']))
    <div class="wa-drawer-foot">
      <a href="{{ url($s['drawer_full_site_url'] ?? '/') }}" target="_blank" rel="noopener">
        <span>{{ $s['drawer_full_site_label'] ?? 'نسخه کامل سایت' }}</span>
        <em aria-hidden="true">↗</em>
      </a>
    </div>
  @endif
</aside>
@endif

@if(!empty($s['show_install_banner']) && ! session('webapp_banner_dismissed'))
<div class="wa-install" id="waInstall" hidden data-smart="{{ !empty($s['smart_install']) ? '1' : '0' }}">
  <div class="wa-install-text">
    <strong id="waInstallTitle">{{ $s['install_banner_text'] ?? 'نصب اپ' }}</strong>
    <small id="waInstallHint">{{ $s['install_ready_text'] ?? 'آماده نصب روی گوشی' }}</small>
  </div>
  <div class="wa-install-actions">
    <button type="button" id="waInstallBtn">نصب</button>
    <button type="button" class="wa-ghost" id="waInstallDismiss" aria-label="بستن">×</button>
  </div>
</div>
<div class="wa-install wa-installed" id="waInstalled" hidden>
  <div class="wa-install-text">
    <strong>{{ $s['installed_badge_text'] ?? 'نصب‌شده روی این دستگاه' }}</strong>
    <small>در حال اجرای نسخه اپ</small>
  </div>
</div>
@endif

@if(!empty($s['show_site_menu']) && !empty($siteMenu))
<nav class="wa-site-menu" aria-label="منوی سایت">
  @foreach($siteMenu as $m)
    @php
      $mHref = str_starts_with($m['url'], 'http') ? $m['url'] : url($m['url']);
      $mKids = collect($m['children'] ?? [])->filter(fn ($c) => !empty($c['label']) && !empty($c['url']))->values();
    @endphp
    @if($mKids->isNotEmpty())
      <details class="wa-site-dd">
        <summary>{{ $m['label'] }}</summary>
        <div class="wa-site-dd-panel">
          <a href="{{ $mHref }}">همه</a>
          @foreach($mKids as $child)
            <a href="{{ str_starts_with($child['url'], 'http') ? $child['url'] : url($child['url']) }}">{{ $child['label'] }}</a>
          @endforeach
        </div>
      </details>
    @else
      <a href="{{ $mHref }}">{{ $m['label'] }}</a>
    @endif
  @endforeach
</nav>
@endif

@if(session('success'))
  <div class="wa-alert wa-ok">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="wa-alert wa-err">{{ session('error') }}</div>
@endif

<main class="wa-main">
  @yield('content')
</main>

@if(!empty($waFooter))
<footer class="wa-footer" role="contentinfo" style="--wa-ft-bg:{{ $waFooter['bg'] }};--wa-ft-accent:{{ $waFooter['accent'] }};--wa-ft-text:{{ $waFooter['text'] }};--wa-ft-muted:{{ $waFooter['muted'] }}">
  <div class="wa-footer-inner">
    <div class="wa-footer-brand">
      <strong>{{ $waFooter['brand'] }}</strong>
      @if(!empty($waFooter['description']))
        <p>{{ \Illuminate\Support\Str::limit($waFooter['description'], 110) }}</p>
      @endif
      <div class="wa-footer-contact">
        @if(!empty($waFooter['phone_digits']))
          <a href="tel:{{ $waFooter['phone_digits'] }}" dir="ltr">{{ $waFooter['phone_display'] }}</a>
        @endif
        @if(!empty($waFooter['email']))
          <a href="mailto:{{ $waFooter['email'] }}" dir="ltr">{{ $waFooter['email'] }}</a>
        @endif
      </div>
    </div>
    <nav class="wa-footer-cols" aria-label="لینک‌های فوتر">
      <div>
        <h2>{{ $waFooter['column1_title'] }}</h2>
        @foreach(($waFooter['column1_links'] ?? []) as $link)
          <a href="{{ str_starts_with($link['url'], 'http') ? $link['url'] : url($link['url']) }}">{{ $link['label'] }}</a>
        @endforeach
      </div>
      <div>
        <h2>{{ $waFooter['column2_title'] }}</h2>
        @foreach(($waFooter['column2_links'] ?? []) as $link)
          <a href="{{ str_starts_with($link['url'], 'http') ? $link['url'] : url($link['url']) }}">{{ $link['label'] }}</a>
        @endforeach
        @if(!empty($waFooter['show_site_link']))
          <a href="{{ url('/') }}" target="_blank" rel="noopener">نسخه کامل سایت</a>
        @endif
      </div>
    </nav>
    <p class="wa-footer-copy">{{ $waFooter['copyright'] }}</p>
  </div>
</footer>
@endif

@if(!empty($s['mobile_bottom_nav']))
<nav class="wa-tabbar">
  @if(!empty($s['show_nav_home']))
    <a class="{{ $tab==='home'?'active':'' }}" href="{{ url('/app') }}"><span class="wa-ico">⌂</span>{{ $s['nav_home_label'] ?? 'خانه' }}</a>
  @endif
  @if(!empty($s['show_nav_shop']))
    <a class="{{ $tab==='shop'?'active':'' }}" href="{{ url('/app/shop') }}"><span class="wa-ico">▣</span>{{ $s['nav_shop_label'] ?? 'فروشگاه' }}</a>
  @endif
  @if(!empty($s['show_nav_cart']))
    <a class="{{ $tab==='cart'?'active':'' }}" href="{{ url('/app/cart') }}"><span class="wa-ico">▢</span>{{ $s['nav_cart_label'] ?? 'سبد' }}@if(($cartCount??0)>0)<i class="wa-dot"></i>@endif</a>
  @endif
  @if(!empty($s['show_nav_account']))
    <a class="{{ $tab==='account'?'active':'' }}" href="{{ url('/app/account') }}"><span class="wa-ico">☺</span>{{ $s['nav_account_label'] ?? 'حساب' }}</a>
  @endif
</nav>
@endif

<script>
  window.WEBAPP = {
    enabled: true,
    dismissUrl: @json(url('/webapp/dismiss-banner')),
    csrf: @json(csrf_token()),
    swUrl: @json(url('/sw.js')),
    offlineMessage: @json($s['offline_message'] ?? ''),
    installHelpIos: @json($s['install_help_ios'] ?? ''),
    installHelpAndroid: @json($s['install_help_android'] ?? ''),
    smartInstall: @json(!empty($s['smart_install'])),
    hideWhenInstalled: @json(!empty($s['hide_install_when_installed'])),
    installOnlyMobile: @json(!empty($s['install_only_mobile'])),
    installedText: @json($s['installed_badge_text'] ?? 'نصب‌شده روی این دستگاه'),
    readyText: @json($s['install_ready_text'] ?? 'آماده نصب روی گوشی'),
    bannerText: @json($s['install_banner_text'] ?? 'نصب اپ سرزمین هارد'),
    drawerEnabled: @json($drawerOn)
  };
</script>
<script src="{{ asset('js/webapp.js') }}?v=15" defer></script>
@php
  \Illuminate\Support\Facades\View::addNamespace('smart-chat', base_path('plugins/SmartChat/resources/views'));
@endphp
<a href="{{ url('/?open_chat=1') }}" aria-label="باز کردن گفت‌وگو" style="position:fixed;right:12px;bottom:76px;z-index:120;display:grid;place-items:center;width:54px;height:54px;border-radius:50%;color:#fff;background:linear-gradient(135deg,#0f766e,#0f172a);box-shadow:0 14px 34px rgba(15,23,42,.24);text-decoration:none;font-size:20px">✦</a>
</body>
</html>
