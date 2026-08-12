@extends('layouts.storefront')
@section('title','کد تأیید کارمند')
@section('content')
<div class="auth-box">
  <h1>کد پیامک</h1>
  <p class="sub">کد به {{ $phoneMasked }} ارسال شد.</p>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
  <form method="post" action="{{ url('/staff/otp') }}">@csrf
    <label>کد تأیید<input type="text" name="code" inputmode="numeric" required autocomplete="one-time-code" autofocus></label>
    <button class="btn btn-primary" type="submit">ورود به پنل</button>
  </form>
  <form method="post" action="{{ url('/staff/otp/resend') }}" style="margin-top:.8rem">@csrf
    <button class="btn btn-outline" type="submit" style="width:100%">ارسال مجدد کد</button>
  </form>
</div>
@endsection
