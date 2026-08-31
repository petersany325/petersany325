@extends('layouts.storefront')
@section('title', 'گارانتی هارد')
@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>گارانتی و استعلام سریال</h2>
      <p>استعلام گارانتی با شماره سریال و پیگیری وضعیت قطعه</p>
    </div>
  </div>
  <div class="hl-edu-grid">
    <article class="hl-edu-card">
      <div class="body">
        <strong>استعلام سریال</strong>
        <p>وضعیت گارانتی را با شماره سریال محصول بررسی کنید.</p>
        <a href="{{ url('/serial-check') }}">استعلام گارانتی</a>
      </div>
    </article>
    <article class="hl-edu-card">
      <div class="body">
        <strong>شرایط گارانتی</strong>
        <p>گارانتی بر اساس برند، سریال و شرایط اعلام‌شده روی کارت محصول است.</p>
        <a href="{{ url('/contact') }}">سوال از فروش</a>
      </div>
    </article>
    <article class="hl-edu-card">
      <div class="body">
        <strong>خرید سازمانی</strong>
        <p>برای تأمین عمده و فاکتور رسمی با واحد فروش تماس بگیرید.</p>
        <a href="{{ url('/contact') }}">تماس با واحد فروش</a>
      </div>
    </article>
  </div>
</section>
@endsection
