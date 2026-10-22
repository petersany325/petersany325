@php
  \Plugins\Studio\Plugin::registerViews();
  $studioHome = class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::get() : [];
  $agency = ! empty($studioHome['enabled']);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', ($studioHome['tagline'] ?? 'تخصصی طراحی سایت و نرم‌افزار').' | سرزمین هارد')</title>
  <meta name="description" content="@yield('description', $studioHome['hero_text'] ?? 'شرکت تخصصی طراحی سایت و نرم‌افزار سرزمین هارد.')">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/studio.css') }}?v=16">
  @if($agency)
    <link rel="stylesheet" href="{{ asset('css/studio-agency.css') }}?v=4">
  @endif
</head>
<body class="st-body{{ $agency ? ' ag-body' : '' }}"@if($agency) style="--ag-accent:{{ $studioHome['accent'] ?? '#7c5cff' }};--ag-bg:{{ $studioHome['bg'] ?? '#070b18' }}"@endif>
@include('studio::nav', ['studioHome' => $studioHome, 'agency' => $agency])
<main>
  @yield('content')
</main>
<footer class="st-foot{{ $agency ? ' ag-foot' : '' }}">
  <div class="{{ $agency ? 'ag-wrap' : 'st-wrap' }}">
    <span>hdd-land.ir — {{ $studioHome['tagline'] ?? 'تخصصی طراحی سایت و نرم‌افزار' }}</span>
    <span>
      <a href="{{ $shopUrl ?? \App\Support\SiteHost::shopUrl('/') }}">فروشگاه قطعات</a>
      ·
      <a href="{{ $receiptUrl ?? \App\Support\SiteHost::receiptUrl() }}" rel="noopener">سامانه قبض</a>
    </span>
  </div>
</footer>
</body>
</html>
