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
  $waIcon = $waOn
    ? \Plugins\WebApp\Plugin::iconUrl($wa['icon_192'] ?? null)
    : asset('images/hdd-land-icon-192.png');
  $path = trim(request()->path(), '/');
  $isHome = $path === '' || $path === '/';
  $isShop = str_starts_with($path, 'products') || str_starts_with($path, 'category') || str_starts_with($path, 'categories');
  $isCart = str_starts_with($path, 'cart') || str_starts_with($path, 'checkout');
  $isAccount = str_starts_with($path, 'account') || str_starts_with($path, 'login') || str_starts_with($path, 'register');
@endphp

@if($waOn)
  <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
  <meta name="theme-color" content="{{ $wa['theme_color'] ?? '#e23d12' }}">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="{{ $wa['short_name'] ?? 'سرزمین‌هارد' }}">
  <link rel="apple-touch-icon" href="{{ $waIcon }}">
  <link rel="icon" type="image/png" href="{{ $waIcon }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/hdd-land-icon-32.png') }}?v=1">
  <link rel="stylesheet" href="{{ asset('css/webapp.css') }}?v=19">
@endif
