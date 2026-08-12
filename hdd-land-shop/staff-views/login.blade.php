@extends('layouts.storefront')
@section('title','ورود کارمندان')
@section('content')
<div class="auth-box">
  <h1>ورود امن کارمندان</h1>
  <p class="sub">پس از تأیید رمز، کد SMS اجباری ارسال می‌شود.</p>
  @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
  <form method="post" action="{{ url('/staff/login') }}">@csrf
    <label>ایمیل یا موبایل<input type="text" name="login" value="{{ old('login') }}" required autocomplete="username"></label>
    <label>رمز عبور<input type="password" name="password" required autocomplete="current-password"></label>
    <label class="chk"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
    <button class="btn btn-primary" type="submit">ادامه · دریافت کد پیامک</button>
    <div class="links">
      <a href="{{ url('/login') }}">ورود مشتریان</a>
      <a href="{{ url('/') }}">فروشگاه</a>
    </div>
  </form>
</div>
@endsection
