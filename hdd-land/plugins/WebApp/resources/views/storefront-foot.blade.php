@php
  $wa = [];
  try {
    if (class_exists(\Plugins\WebApp\Plugin::class)) {
      $wa = \Plugins\WebApp\Plugin::settings();
    }
  } catch (\Throwable $e) {
    $wa = [];
  }
  $waOn = ! empty($wa['enabled']);
  $path = trim(request()->path(), '/');
  $isHome = $path === '';
  $isApp = str_starts_with($path, 'app');
  $isShop = str_starts_with($path, 'products') || str_starts_with($path, 'category') || str_starts_with($path, 'categories') || str_starts_with($path, 'app/shop') || str_starts_with($path, 'app/product');
  $isCart = str_starts_with($path, 'cart') || str_starts_with($path, 'checkout') || str_starts_with($path, 'app/cart');
  $isAccount = str_starts_with($path, 'account') || $path === 'login' || $path === 'register' || str_starts_with($path, 'app/account');
  $showStoreNav = $waOn && ! empty($wa['storefront_bottom_nav']) && ! $isApp;
@endphp

@if($waOn && ! empty($wa['show_install_banner']) && ! session('webapp_banner_dismissed') && ! $isApp)
  <div class="site-wa-install container" id="waInstall" hidden data-smart="{{ !empty($wa['smart_install']) ? '1' : '0' }}">
    <div class="wa-install-text" style="display:grid;gap:.1rem">
      <strong id="waInstallTitle">{{ $wa['install_banner_text'] ?? 'نصب وب‌اپ روی صفحه اصلی گوشی' }}</strong>
      <small id="waInstallHint" style="opacity:.85;font-size:.75rem">{{ $wa['install_ready_text'] ?? 'آماده نصب روی گوشی' }}</small>
    </div>
    <div style="display:flex;gap:.35rem;align-items:center">
      <button type="button" data-wa-install id="waInstallBtn">نصب</button>
      <button type="button" data-wa-dismiss id="waInstallDismiss" style="background:transparent;color:#6b7280;border:0;font-size:1.1rem;cursor:pointer">×</button>
    </div>
  </div>
  <div class="site-wa-install site-wa-installed container" id="waInstalled" hidden style="background:#edfaf3;border-color:#b7e4cb;color:#0f7a4b">
    <span>{{ $wa['installed_badge_text'] ?? 'نصب‌شده روی این دستگاه' }}</span>
  </div>
@endif

@if($showStoreNav)
<nav class="site-wa-tabbar" aria-label="منوی موبایل وب‌اپ">
  @if(!empty($wa['show_nav_home']))
    <a class="{{ $isHome ? 'active' : '' }}" href="{{ !empty($wa['force_app_on_mobile']) ? url('/app') : url('/') }}"><span>⌂</span>{{ $wa['nav_home_label'] ?? 'خانه' }}</a>
  @endif
  @if(!empty($wa['show_nav_shop']))
    <a class="{{ $isShop ? 'active' : '' }}" href="{{ url('/app/shop') }}"><span>▣</span>{{ $wa['nav_shop_label'] ?? 'فروشگاه' }}</a>
  @endif
  @if(!empty($wa['show_nav_cart']))
    <a class="{{ $isCart ? 'active' : '' }}" href="{{ url('/app/cart') }}"><span>▢</span>{{ $wa['nav_cart_label'] ?? 'سبد' }}</a>
  @endif
  @if(!empty($wa['show_nav_account']))
    <a class="{{ $isAccount ? 'active' : '' }}" href="{{ url('/app/account') }}"><span>☺</span>{{ $wa['nav_account_label'] ?? 'حساب' }}</a>
  @endif
</nav>
@endif

@if($waOn)
<script>
  window.WEBAPP = {
    enabled: true,
    dismissUrl: @json(url('/webapp/dismiss-banner')),
    csrf: @json(csrf_token()),
    swUrl: @json(url('/sw.js')),
    offlineMessage: @json($wa['offline_message'] ?? ''),
    installHelpIos: @json($wa['install_help_ios'] ?? ''),
    installHelpAndroid: @json($wa['install_help_android'] ?? ''),
    smartInstall: @json(!empty($wa['smart_install'])),
    hideWhenInstalled: @json(!empty($wa['hide_install_when_installed'])),
    installOnlyMobile: @json(!empty($wa['install_only_mobile'])),
    installedText: @json($wa['installed_badge_text'] ?? 'نصب‌شده روی این دستگاه'),
    readyText: @json($wa['install_ready_text'] ?? 'آماده نصب روی گوشی'),
    bannerText: @json($wa['install_banner_text'] ?? 'نصب اپ سرزمین هارد'),
    forceAppOnMobile: @json(!empty($wa['force_app_on_mobile']))
  };
  @if(!empty($wa['force_app_on_mobile']))
  (function () {
    try {
      var ua = navigator.userAgent || '';
      var mobile = /Android|iPhone|iPad|iPod|Mobile/i.test(ua) ||
        ((window.matchMedia && window.matchMedia('(max-width: 860px)').matches) &&
        (navigator.maxTouchPoints > 0 || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches)));
      var path = location.pathname.replace(/\/+$/, '') || '/';
      var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
      if (mobile && !standalone && (path === '/' || path === '') && !sessionStorage.getItem('wa_forced')) {
        sessionStorage.setItem('wa_forced', '1');
        location.replace(@json(url('/app')));
      }
    } catch (e) {}
  })();
  @endif
</script>
<script src="{{ asset('js/webapp.js') }}?v=10" defer></script>
@endif
