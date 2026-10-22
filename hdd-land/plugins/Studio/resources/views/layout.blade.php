<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'تخصصی طراحی سایت و نرم‌افزار | سرزمین هارد')</title>
  <meta name="description" content="@yield('description', 'شرکت تخصصی طراحی سایت و نرم‌افزار سرزمین هارد، با لایه هوش مصنوعی.')">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/studio.css') }}?v=14">
</head>
<body class="st-body">
@include('studio::nav')
<main>
  @yield('content')
</main>
<footer class="st-foot">
  <div class="st-wrap">
    <span>hdd-land.ir — تخصصی طراحی سایت و نرم‌افزار</span>
    <span>
      <a href="{{ $shopUrl ?? \App\Support\SiteHost::shopUrl('/') }}">فروشگاه قطعات</a>
      ·
      <a href="{{ $receiptUrl ?? \App\Support\SiteHost::receiptUrl() }}" rel="noopener">سامانه قبض</a>
    </span>
  </div>
</footer>
</body>
</html>
