@extends('layouts.storefront')
@section('title', 'تماس با ما')
@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>تماس با واحد فروش</h2>
      <p>برای استعلام قیمت، خرید سازمانی و پشتیبانی فروشگاه با ما در ارتباط باشید.</p>
    </div>
  </div>
  <div class="hl-edu-grid">
    <article class="hl-edu-card">
      <div class="body">
        <strong>تلفن</strong>
        <p>{{ \App\Models\Setting::getValue('shop_phone', '01144447220') }}</p>
        <a href="tel:{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::getValue('shop_phone', '01144447220')) }}">تماس تلفنی</a>
      </div>
    </article>
    <article class="hl-edu-card">
      <div class="body">
        <strong>آدرس</strong>
        <p>{{ \App\Models\Setting::getValue('shop_address', 'مازندران، آمل') }}</p>
        <a href="{{ url('/products') }}">مشاهده فروشگاه</a>
      </div>
    </article>
    <article class="hl-edu-card">
      <div class="body">
        <strong>پیگیری سفارش</strong>
        <p>وضعیت سفارش فروشگاهی را با کد پیگیری ببینید.</p>
        <a href="{{ url('/orders/track') }}">پیگیری سفارش</a>
      </div>
    </article>
  </div>
</section>
@endsection
