@extends('layouts.storefront')
@section('title', 'درباره ما')
@section('content')
<section class="section hl-about-section">
  <div class="hl-about">
    <img src="{{ asset('images/home/about.jpg') }}" alt="معرفی سرزمین هارد" width="1200" height="800" loading="lazy">
    <div>
      <div class="hl-head" style="margin-bottom:.6rem"><div><h2>معرفی سرزمین هارد</h2></div></div>
      <p class="muted">سرزمین هارد تأمین‌کننده تخصصی هارد دیسک، SSD و تجهیزات ذخیره‌سازی برای فروشگاه و سازمان است. تمرکز ما روی موجودی واقعی، گارانتی شفاف و مشاوره خرید است.</p>
      <p class="muted">از قطعه تکی تا تأمین سازمانی، مسیر مشخصی برای استعلام، فاکتور و پشتیبانی فنی دارید.</p>
      <div class="hl-stats">
        <div class="hl-stat"><b>تأمین تخصصی</b><span>هارد، SSD، NVMe</span></div>
        <div class="hl-stat"><b>خرید سازمانی</b><span>استعلام و پیش‌فاکتور</span></div>
        <div class="hl-stat"><b>گارانتی شفاف</b><span>استعلام با سریال</span></div>
      </div>
      <div class="hl-hero__actions" style="margin-top:1rem">
        <a class="btn-hl btn-hl-primary" href="{{ url('/products') }}">کاتالوگ محصولات</a>
        <a class="btn-hl btn-hl-ghost" href="{{ url('/contact') }}">تماس با واحد فروش</a>
      </div>
    </div>
  </div>
</section>
@endsection
