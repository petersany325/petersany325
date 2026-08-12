@extends('layouts.hdd-land')
@section('title', 'تماس با ما')
@section('content')
<section class="section">
  <div class="section-head">
    <h2>تماس و درخواست بازیابی</h2>
    <p>برای پذیرش دستگاه، گارانتی یا مشاوره آموزشی از این صفحه اقدام کنید.</p>
  </div>
  <div class="dual-paths">
    <article class="dual corp">
      <div class="code">CONTACT</div>
      <h3>راه‌های ارتباطی</h3>
      <p>تلفن: {{ \App\Models\Setting::getValue('shop_phone', '۰۲۱-۰۰۰۰۰۰۰۰') }}</p>
      <p>{{ \App\Models\Setting::getValue('shop_address', 'تهران، ایران') }}</p>
      <div class="actions">
        <a class="btn btn-red" href="tel:{{ preg_replace('/\D+/', '', \App\Models\Setting::getValue('shop_phone', '02100000000')) }}">تماس تلفنی</a>
      </div>
    </article>
    <article class="dual shop">
      <div class="code">SHOP HELP</div>
      <h3>پشتیبانی خرید</h3>
      <p>برای سفارش نرم‌افزار، لایسنس و پیگیری خرید به فروشگاه بروید.</p>
      <div class="actions">
        <a class="btn btn-blue" href="{{ url('/products') }}">فروشگاه</a>
        <a class="btn btn-ghost" href="{{ url('/orders/track') }}" style="border-color:#D2DCE8;color:#0A121C">پیگیری سفارش</a>
      </div>
    </article>
  </div>
</section>
@endsection
