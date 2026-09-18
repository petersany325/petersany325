@extends('layouts.storefront')

@section('title', $copy['title'].' | سرزمین هارد')

@section('content')
@php
  $bullets = \Plugins\WarrantyDesk\src\Support\PageCopy::lines((string) ($copy['bullets'] ?? ''));
  $steps = \Plugins\WarrantyDesk\src\Support\PageCopy::lines((string) ($copy['steps'] ?? ''));
@endphp
<link rel="stylesheet" href="{{ asset('css/warranty-register.css') }}?v=1">
<section class="ws-page wr-page">
  <header class="ws-hero">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <a href="{{ url('/serial-check') }}">گارانتی</a>
        <span>/</span>
        <span>ثبت گارانتی</span>
      </nav>
      <p class="ws-kicker">{{ $copy['kicker'] }}</p>
      <h1 class="ws-brand">{{ $copy['title'] }}</h1>
      <p class="ws-lead">{{ $copy['lead'] }}</p>
      <div class="ws-cta-row">
        <a class="ws-btn ws-btn--accent" href="#wr-form">{{ $copy['cta_label'] }}</a>
        <a class="ws-btn ws-btn--ghost" href="{{ url('/warranty-register/status') }}">پیگیری درخواست</a>
        <a class="ws-btn ws-btn--ghost" href="{{ url('/serial-check') }}">استعلام سریال مشتری</a>
      </div>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    <p class="ws-sub">{{ $copy['intro'] }}</p>
    @if($bullets)
      <ul class="ws-bullets">
        @foreach($bullets as $b)<li>{{ $b }}</li>@endforeach
      </ul>
    @endif
  </div>

  @if($steps)
    <div class="ws-wrap ws-section">
      <h2>مسیر کار</h2>
      <ol class="ws-steps">
        @foreach($steps as $s)<li>{{ $s }}</li>@endforeach
      </ol>
    </div>
  @endif

  @if(!empty($packages))
    <div class="ws-wrap ws-section">
      <h2>بسته‌های پیشنهادی</h2>
      <p class="ws-sub">مبلغ نهایی بعد از بررسی پرونده اعلام می‌شود؛ این‌ها نقطه شروع مذاکره است.</p>
      <div class="wr-packs">
        @foreach($packages as $p)
          <article class="wr-pack">
            <strong>{{ $p['name'] }}</strong>
            @if($p['months'] > 0)<span>{{ $p['months'] }} ماه پوشش</span>@endif
            @if($p['price'])<em>{{ number_format($p['price']) }} تومان</em>@else<em>قیمت پس از بررسی</em>@endif
          </article>
        @endforeach
      </div>
    </div>
  @endif

  <div class="ws-wrap ws-section" id="wr-form">
    <h2>{{ $copy['form_title'] }}</h2>
    <p class="ws-sub">{{ $copy['note'] }}</p>
    <form class="wr-form" method="post" action="{{ url('/warranty-register') }}">
      @csrf
      <div class="wr-grid">
        <label>نوع متقاضی
          <select name="applicant_type" required>
            @foreach($types as $k => $lab)
              <option value="{{ $k }}" @selected(old('applicant_type', 'shop') === $k)>{{ $lab }}</option>
            @endforeach
          </select>
        </label>
        <label>نام فروشگاه / شرکت / سازمان
          <input name="org_name" required maxlength="190" value="{{ old('org_name') }}" placeholder="مثلاً فروشگاه رایانه شمال">
        </label>
        <label>نام مسئول پیگیری
          <input name="contact_name" required maxlength="120" value="{{ old('contact_name', auth()->user()->name ?? '') }}">
        </label>
        <label>موبایل (پیامک وضعیت)
          <input name="mobile" required maxlength="30" dir="ltr" value="{{ old('mobile', auth()->user()->mobile ?? '') }}" placeholder="0912…">
        </label>
        <label>تلفن ثابت
          <input name="phone" maxlength="30" dir="ltr" value="{{ old('phone') }}">
        </label>
        <label>شهر
          <input name="city" maxlength="80" value="{{ old('city') }}">
        </label>
        <label>نوع کالا
          <input name="product_kind" maxlength="120" value="{{ old('product_kind') }}" placeholder="HDD / SSD / NVMe / رم">
        </label>
        <label>برند و مدل
          <input name="brand_model" maxlength="190" value="{{ old('brand_model') }}">
        </label>
        <label>تعداد تقریبی
          <input name="qty" type="number" min="1" max="9999" value="{{ old('qty', 1) }}">
        </label>
        @if(!empty($packages))
          <label>بسته مورد نظر
            <select name="package_name">
              <option value="">بعد از بررسی تصمیم می‌گیرم</option>
              @foreach($packages as $p)
                <option value="{{ $p['name'] }}" @selected(old('package_name') === $p['name'])>{{ $p['name'] }}</option>
              @endforeach
            </select>
          </label>
        @endif
      </div>
      <label>سریال‌ها (اختیاری، هر خط یک سریال)
        <textarea name="serials" rows="3" maxlength="4000" placeholder="اگر لیست سریال آماده دارید اینجا بچسبانید">{{ old('serials') }}</textarea>
      </label>
      <label>توضیح درخواست
        <textarea name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
      </label>
      <button class="ws-btn ws-btn--accent wr-submit" type="submit">{{ $copy['cta_label'] }}</button>
    </form>
  </div>
</section>
@endsection
