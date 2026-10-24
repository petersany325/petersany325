@php
  \Plugins\Studio\Plugin::registerViews();
  $studioHome = class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::get() : [];
  $agency = ! empty($studioHome['enabled']);
  $hlAdminBar = null;
  if (class_exists(\App\Support\AdminToolbar::class)) {
    try { $hlAdminBar = \App\Support\AdminToolbar::current(); } catch (\Throwable $e) {}
  }
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl"@if($hlAdminBar) class="has-hl-adminbar"@endif>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', ($studioHome['tagline'] ?? 'طراحی سایت، نرم‌افزار و هوش مصنوعی').' | فراز نت آمل')</title>
  <meta name="description" content="@hasSection('description')@yield('description')@else{{ $studioHome['hero_text'] ?? 'طراحی سایت حرفه‌ای با فراز نت آمل؛ سایت شرکتی، فروشگاهی و سامانه‌های اختصاصی لاراول.' }}@endif">
  @if($agency && class_exists(\App\Support\StudioHomeConfig::class) && \App\Support\StudioHomeConfig::mobileWebAppOn($studioHome))
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
  @if($agency)
    <link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=15">
  @endif
</head>
<body class="st-body{{ $agency ? ' ag-body' : '' }}{{ ($agency && class_exists(\App\Support\StudioHomeConfig::class) && \App\Support\StudioHomeConfig::mobileWebAppOn($studioHome)) ? ' has-ag-tabbar' : '' }}{{ $hlAdminBar ? ' has-hl-adminbar' : '' }}"@if($agency) style="--ag-accent:{{ $studioHome['accent'] ?? '#7c5cff' }};--ag-bg:{{ $studioHome['bg'] ?? '#070b18' }}"@endif>
@if(view()->exists('partials.admin-toolbar'))
  @include('partials.admin-toolbar')
@endif
@include('studio::nav', ['studioHome' => $studioHome, 'agency' => $agency])
<main>
  @yield('content')
</main>
<footer class="st-foot{{ $agency ? ' ag-foot' : '' }}">
  <div class="{{ $agency ? 'ag-wrap' : 'st-wrap' }}">
    <span>{{ $studioHome['brand'] ?? 'شرکت فراز نت آمل (سرزمین هارد)' }} — {{ $studioHome['tagline'] ?? 'طراحی سایت، نرم‌افزار و هوش مصنوعی' }}</span>
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
      <a href="{{ $shopUrl ?? \App\Support\SiteHost::shopUrl('/') }}">فروشگاه قطعات</a>
      ·
      <a href="{{ $receiptUrl ?? \App\Support\SiteHost::receiptUrl() }}" rel="noopener">سامانه قبض</a>
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
