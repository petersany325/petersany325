@extends('layouts.storefront')

@section('title', ($copy['lookup_title'] ?? 'پیگیری درخواست گارانتی').' | سرزمین هارد')

@section('content')
<link rel="stylesheet" href="{{ asset('css/warranty-register.css') }}?v=1">
<section class="ws-page wr-page">
  <header class="ws-hero">
    <div class="ws-wrap">
      <nav class="ws-crumbs" aria-label="مسیر">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <a href="{{ url('/warranty-register') }}">ثبت گارانتی</a>
        <span>/</span>
        <span>پیگیری</span>
      </nav>
      <p class="ws-kicker">{{ $copy['kicker'] }}</p>
      <h1 class="ws-brand">{{ $copy['lookup_title'] }}</h1>
      <p class="ws-lead">{{ $copy['lookup_hint'] }}</p>
    </div>
  </header>

  <div class="ws-wrap ws-section">
    <form class="wr-lookup" method="get" action="{{ url('/warranty-register/status') }}">
      <input name="code" value="{{ $code }}" placeholder="مثلاً WR-A1B2C3D4" dir="ltr" maxlength="24">
      <button class="ws-btn ws-btn--accent" type="submit">مشاهده وضعیت</button>
    </form>

    @if(!empty($notFound))
      <p class="wr-miss">کد پیدا نشد. دوباره بررسی کنید یا درخواست جدید بدهید.</p>
    @endif

    @if($row)
      <article class="wr-card">
        <header>
          <strong>{{ $row->public_code }}</strong>
          <span class="wr-st wr-st--{{ $row->status }}">{{ $row->statusLabel() }}</span>
        </header>
        <dl>
          <div><dt>متقاضی</dt><dd>{{ $row->org_name }} · {{ $row->applicantLabel() }}</dd></div>
          <div><dt>مسئول</dt><dd>{{ $row->contact_name }}</dd></div>
          @if($row->product_kind || $row->brand_model)
            <div><dt>کالا</dt><dd>{{ trim($row->product_kind.' '.$row->brand_model) }} @if($row->qty) ({{ $row->qty }} عدد)@endif</dd></div>
          @endif
          @if($row->package_name)
            <div><dt>بسته</dt><dd>{{ $row->package_name }}</dd></div>
          @endif
          @if($row->quote_amount || $row->quote_months)
            <div><dt>پیشنهاد</dt><dd>
              @if($row->quote_amount){{ number_format((int) $row->quote_amount) }} تومان@endif
              @if($row->quote_months) · {{ $row->quote_months }} ماه@endif
            </dd></div>
          @endif
          @if($row->admin_note && in_array($row->status, ['quote', 'accepted', 'active', 'rejected'], true))
            <div><dt>یادداشت</dt><dd>{{ $row->admin_note }}</dd></div>
          @endif
        </dl>
        <p class="wr-hint">با هر تغییر وضعیت، پیامک به {{ $row->mobile }} ارسال می‌شود.</p>
      </article>
    @endif

    <p class="ws-sub" style="margin-top:1.2rem">
      <a href="{{ url('/warranty-register') }}">ثبت درخواست جدید</a>
      ·
      <a href="{{ url('/serial-check') }}">استعلام سریال مشتری</a>
    </p>
  </div>
</section>
@endsection
