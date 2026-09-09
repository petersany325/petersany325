@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $name = $user->name ?? ($user->full_name ?? 'کاربر');
  $initial = mb_substr($name, 0, 1);
@endphp

@if($user)
  <div class="wa-profile">
    <div class="wa-avatar">{{ $initial }}</div>
    <div>
      <strong>{{ $name }}</strong>
      <small>{{ $user->mobile ?? $user->email ?? '' }}</small>
    </div>
  </div>

  <div class="wa-menu">
    @if(!empty($s['account_show_orders']))
      <a href="{{ url('/account/orders') }}">سفارش‌های من <span>‹</span></a>
    @endif
    <a href="{{ url('/account/invoices') }}">فاکتورها <span>‹</span></a>
    @if(!empty($s['account_show_wallet']))
      <a href="{{ url('/account/wallet') }}">کیف پول <span>‹</span></a>
    @endif
    @if(!empty($s['account_show_profile']))
      <a href="{{ url('/account/profile') }}">پروفایل <span>‹</span></a>
    @endif
    @if(!empty($s['account_show_tickets']))
      <a href="{{ url('/account/tickets') }}">تیکت پشتیبانی <span>‹</span></a>
    @endif
    @if(!empty($s['account_show_track']))
      <a href="{{ url('/orders/track') }}">پیگیری سفارش <span>‹</span></a>
    @endif
    <a href="{{ url('/account/serials') }}">سریال‌ها و گارانتی من <span>‹</span></a>
    <a href="{{ url('/serial-check') }}">استعلام گارانتی <span>‹</span></a>
    <a href="{{ url('/account') }}">کارتابل کامل مشتری <span>‹</span></a>
    @if(!empty($s['account_show_full_site']))
      <a href="{{ url('/') }}" target="_blank" rel="noopener">نسخه کامل سایت <span>‹</span></a>
    @endif
  </div>

  @if($orders->isNotEmpty())
    <div class="wa-section-head"><strong>آخرین سفارش‌ها</strong></div>
    <div class="wa-orders">
      @foreach($orders as $o)
        <a class="wa-order" href="{{ url('/account/orders/'.$o->id) }}">
          <strong>#{{ $o->order_number ?? $o->id }}</strong>
          <span>{{ $fmt($o->total ?? 0) }} تومان</span>
          <small>{{ $o->status ?? '' }} · {{ $o->payment_status ?? '' }}</small>
        </a>
      @endforeach
    </div>
  @endif

  <form method="post" action="{{ url('/logout') }}" style="margin:1rem">
    @csrf
    <button class="wa-btn wa-btn-ghost wa-btn-block" type="submit" style="border-color:#f3c1bb;color:#a32012;background:#fff1f0">خروج از حساب</button>
  </form>
@else
  <div class="wa-profile">
    <div class="wa-avatar">؟</div>
    <div>
      <strong>مهمان</strong>
      <small>برای خرید وارد شوید</small>
    </div>
  </div>
  <div class="wa-auth-actions">
    <a class="wa-btn wa-btn-primary wa-btn-block" href="{{ url('/login') }}">ورود</a>
    <a class="wa-btn wa-btn-ghost wa-btn-block" href="{{ url('/register') }}">ثبت‌نام</a>
  </div>
  <div class="wa-menu" style="margin-top:1rem">
    @if(!empty($s['account_show_track']))
      <a href="{{ url('/orders/track') }}">پیگیری سفارش <span>‹</span></a>
    @endif
    <a href="{{ url('/serial-check') }}">استعلام گارانتی <span>‹</span></a>
    <a href="{{ url('/contact') }}">تماس با ما <span>‹</span></a>
  </div>
@endif
@endsection
