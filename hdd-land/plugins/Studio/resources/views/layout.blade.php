<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'استودیو سرزمین هارد')</title>
  <meta name="description" content="@yield('description', 'طراحی سایت، نرم‌افزار حسابداری تحت وب، پروژه هوش مصنوعی و نرم‌افزار قبض')">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/studio.css') }}?v=1">
</head>
<body class="st-body">
@include('studio::nav')
<main>
  @yield('content')
</main>
<footer class="st-foot">
  <div class="st-wrap">
    <span>hdd-land.ir — استودیو سایت و نرم‌افزار</span>
    <span>
      <a href="{{ $shopUrl ?? \App\Support\SiteHost::shopUrl('/') }}">فروشگاه قطعات</a>
      ·
      <a href="{{ $receiptUrl ?? \App\Support\SiteHost::receiptUrl() }}" rel="noopener">سامانه قبض</a>
    </span>
  </div>
</footer>
</body>
</html>
