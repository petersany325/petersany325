@extends('layouts.admin')
@section('title', 'ورود ادمین')
@section('body')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}?v=2" />
<style>
  body{background:#f0f0f1!important;font-family:Vazirmatn,Tahoma,sans-serif}
  .wp-login-wrap{min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:3.5rem 1rem 2rem}
  .wp-login-card{width:min(100%,22rem);background:#fff;border:1px solid #c3c4c7;border-radius:.35rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .wp-login-card h2{margin:0 0 1rem;font-size:.95rem}
  .wp-login-card .btn{width:100%;background:#0f766e;color:#fff;border:0;border-radius:.25rem;min-height:2.4rem;font-weight:700}
  .wp-login-card input{border-radius:.25rem;background:#fff;border:1px solid #c3c4c7}
  .login-gate-note{width:min(100%,22rem);margin-top:1.1rem;text-align:center;font-size:.8125rem;color:#646970}
  .login-gate-note a{color:#0f766e;font-weight:600;text-decoration:none}
</style>
<div class="wp-login-wrap">
  <aside class="login-gate-brand" aria-label="لوگوی ورود">
    <div class="login-gate-brand__top">
      <div class="login-gate-brand__mark">آ</div>
      <span>پنل مدیریت</span>
    </div>
    <div class="login-gate-brand__hero">
      <h1 class="brand-name">مؤسسه حقوقی آریان</h1>
    </div>
  </aside>
  <div class="wp-login-card card">
    <h2>ورود به پنل مدیریت</h2>
    @if ($errors->any())
      <div class="alert" style="background:rgba(160,50,50,.1);color:#8a2a2a">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('admin.login.submit') }}">
      @csrf
      <label>ایمیل مدیریت
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
      </label>
      <label>رمز عبور
        <input type="password" name="password" required>
      </label>
      <label style="display:flex;align-items:center;gap:.5rem;font-weight:500">
        <input type="checkbox" name="remember" value="1" style="width:auto"> مرا به خاطر بسپار
      </label>
      <button class="btn" type="submit">ورود به پنل</button>
    </form>
  </div>
  <p class="login-gate-note">← <a href="{{ url('/') }}">بازگشت به سایت</a></p>
</div>
@endsection
