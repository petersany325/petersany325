@extends('layouts.hdd-land')
@section('title', 'خدمات تخصصی')
@section('content')
<section class="section">
  <div class="section-head">
    <h2>خدمات بازیابی و تعمیر استوریج</h2>
    <p>بازیابی اطلاعات هارد، SSD، فلش، سرور، DVR و موبایل — همراه با تعمیر استوریج.</p>
  </div>
  <div class="sub-paths">
    <article class="sub">
      <h4>بازیابی داده</h4>
      <p>HDD / SSD / فلش / موبایل / DVR / سرور</p>
      <a href="{{ url('/contact') }}">ثبت درخواست ←</a>
    </article>
    <article class="sub">
      <h4>تعمیر استوریج</h4>
      <p>رفع ایراد سخت‌افزاری و آماده‌سازی برای بازیابی</p>
      <a href="{{ url('/services/about-recovery') }}">تعریف تخصصی ←</a>
    </article>
    <article class="sub">
      <h4>گارانتی هارد</h4>
      <p>قبول گارانتی شرکت‌ها با مدارک لازم</p>
      <a href="{{ url('/warranty') }}">ورود به گارانتی ←</a>
    </article>
  </div>
</section>
@endsection
