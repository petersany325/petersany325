@extends('layouts.storefront')
@section('title', 'خدمات سازمانی')
@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>خدمات تأمین سازمانی</h2>
      <p>تأمین هارد، SSD و تجهیزات ذخیره‌سازی برای سازمان، شعب و پروژه‌ها</p>
    </div>
  </div>
  <div class="sub-paths">
    <article class="sub">
      <h4>تأمین سازمانی</h4>
      <p>استعلام، پیش‌فاکتور و تأمین عمده قطعات ذخیره‌سازی</p>
      <a href="{{ url('/contact') }}">ثبت درخواست ←</a>
    </article>
    <article class="sub">
      <h4>تجهیز NAS و سرور</h4>
      <p>انتخاب ظرفیت مناسب برای آرشیو، بکاپ و شعب</p>
      <a href="{{ url('/products') }}">کاتالوگ محصولات ←</a>
    </article>
    <article class="sub">
      <h4>گارانتی و سریال</h4>
      <p>استعلام گارانتی با شماره سریال محصول</p>
      <a href="{{ url('/warranty') }}">ورود به گارانتی ←</a>
    </article>
  </div>
</section>
@endsection
