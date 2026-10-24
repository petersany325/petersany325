@extends('studio::layout')
@section('title', ($title ?? 'ورود').' | فراز نت آمل')
@section('description', 'ورود و ثبت‌نام با پیامک در شرکت فراز نت آمل')
@section('content')
@php
  $mode = $mode ?? 'customer';
  $tab = $tab ?? 'login';
  $step = $step ?? 'phone';
  $phone = $phone ?? '';
  $name = $name ?? '';
  $isStaff = $mode === 'staff';
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <div class="ag-auth-card">
      <p class="ag-kicker">{{ $isStaff ? 'پنل داخلی' : 'حساب کاربری' }}</p>
      <h1>{{ $isStaff ? 'ورود کارمند' : 'ورود و ثبت‌نام کاربر' }}</h1>
      <p class="ag-lead">{{ $isStaff ? 'ورود کارمندان با کد پیامک روی شماره ثبت‌شده در پنل است. ثبت‌نام کارمند فقط توسط مدیر انجام می‌شود.' : 'با شماره موبایل وارد شوید یا حساب بسازید. کد تایید پیامک می‌شود.' }}</p>

      @if(session('success'))
        <div class="ag-note">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="ag-note ag-note--err">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="ag-note ag-note--err">{{ $errors->first() }}</div>
      @endif

      @unless($isStaff)
        <div class="ag-auth-tabs" role="tablist">
          <a class="{{ $tab === 'login' ? 'is-on' : '' }}" href="{{ url('/auth/customer') }}">ورود</a>
          <a class="{{ $tab === 'register' ? 'is-on' : '' }}" href="{{ url('/auth/customer?tab=register') }}">ثبت‌نام</a>
        </div>
      @endunless

      @if($step === 'code')
        <form class="ag-auth-form" method="post" action="{{ url($isStaff ? '/auth/staff/verify' : '/auth/customer/verify') }}">
          @csrf
          <input type="hidden" name="intent" value="{{ $isStaff ? 'login' : $tab }}">
          <input type="hidden" name="name" value="{{ $name }}">
          <label>موبایل
            <input name="phone" value="{{ $phone }}" dir="ltr" inputmode="tel" readonly>
          </label>
          <label>کد پیامک‌شده
            <input name="code" value="{{ old('code') }}" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required autofocus>
          </label>
          <button class="ag-btn ag-btn--on" type="submit">تایید و ورود</button>
        </form>
        <form method="post" action="{{ url('/auth/restart') }}" class="ag-auth-alt">
          @csrf
          <input type="hidden" name="mode" value="{{ $mode }}">
          <button type="submit">تغییر شماره / ارسال دوباره</button>
        </form>
      @else
        <form class="ag-auth-form" method="post" action="{{ url($isStaff ? '/auth/staff/sms' : '/auth/customer/sms') }}">
          @csrf
          <input type="hidden" name="intent" value="{{ $isStaff ? 'login' : $tab }}">
          @if(! $isStaff && $tab === 'register')
            <label>نام و نام خانوادگی
              <input name="name" value="{{ $name }}" required maxlength="120" autocomplete="name">
            </label>
          @endif
          <label>شماره موبایل
            <input name="phone" value="{{ $phone }}" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="09121234567" required>
          </label>
          <button class="ag-btn ag-btn--on" type="submit">{{ $isStaff || $tab === 'login' ? 'ارسال کد ورود' : 'ارسال کد ثبت‌نام' }}</button>
        </form>
      @endif

      <div class="ag-auth-switch">
        @if($isStaff)
          <a href="{{ url('/auth/customer') }}">ورود کاربر</a>
        @else
          <a href="{{ url('/auth/staff') }}">ورود کارمند</a>
          <a href="{{ url('/login') }}">ورود با ایمیل و رمز</a>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
