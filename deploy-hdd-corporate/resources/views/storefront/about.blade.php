@extends('layouts.hdd-land')
@section('title', 'درباره ما')
@section('content')
<section class="section">
  <div class="section-head">
    <h2>معرفی {{ \App\Models\Setting::getValue('shop_name', 'HDD Land') }}</h2>
    <p>مرکز تخصصی بازیابی اطلاعات، تعمیر استوریج، گارانتی هارد، آموزش و فروش نرم‌افزار.</p>
  </div>
  <div class="sub-paths">
    <article class="sub"><h4>تخصص</h4><p>بازیابی و تعمیر انواع حافظه و استوریج.</p></article>
    <article class="sub"><h4>اعتماد</h4><p>محرمانگی داده و اعلام نتیجه قبل از اقدام.</p></article>
    <article class="sub"><h4>دو مسیر</h4><p>خدمات شرکتی + فروشگاه نرم‌افزار در یک برند.</p></article>
  </div>
</section>
@endsection
