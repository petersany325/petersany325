@extends('layouts.admin')
@section('title', 'ورود ادمین')
@section('body')
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}?v=1" />
<style>
  body{background:#eef3f7!important}
  .legacy-login{
    display:grid;grid-template-columns:1.1fr 1fr;min-height:100vh;
  }
  @media(max-width:960px){.legacy-login{grid-template-columns:1fr}}
  .legacy-login .card{
    width:min(100%,26.5rem);margin:auto;border:0;border-radius:1.35rem;
    box-shadow:0 24px 60px rgba(6,32,51,.1);padding:1.75rem;
  }
  .legacy-login h2{font-family:Literata,Vazirmatn,serif;margin:0 0 .4rem;font-size:1.85rem}
  .legacy-login .lead{color:#5b6b7a;margin:0 0 1.25rem;line-height:1.75}
  .legacy-login .btn{
    width:100%;background:linear-gradient(120deg,#0f766e,#0d9488);color:#fff;border:0;
    border-radius:.9rem;min-height:2.95rem;font-weight:800
  }
  .legacy-login input{border-radius:.85rem;background:#fff}
</style>
<div class="legacy-login">
  <aside class="login-gate-brand" aria-label="معرفی برند">
    <div class="login-gate-brand__top">
      <div class="login-gate-brand__mark">آ</div>
      <span>پنل مدیریت امن</span>
    </div>
    <div class="login-gate-brand__hero">
      <h1 class="brand-name">مؤسسه حقوقی آریان</h1>
      <div class="brand-rule" aria-hidden="true"></div>
      <p>ورود اختصاصی مدیران برای پیگیری نوبت‌ها، موکلین و محتوای وب‌سایت.</p>
    </div>
    <div class="login-gate-brand__foot">
      <span><b>امن</b> · نشست رمزگذاری‌شده</span>
      <span><b>سریع</b> · دسترسی یکپارچه به داشبورد</span>
    </div>
  </aside>
  <div style="display:flex;align-items:center;justify-content:center;padding:1.25rem">
    <div class="card">
      <h2>خوش آمدید</h2>
      <p class="lead">برای مدیریت نوبت‌ها، موکلین و محتوای سایت وارد شوید.</p>
      @if ($errors->any())
        <div class="alert" style="background:rgba(160,50,50,.1);color:#8a2a2a">{{ $errors->first() }}</div>
      @endif
      <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <label>ایمیل مدیریت
          <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
        </label>
        <label>رمز عبور
          <input type="password" name="password" required placeholder="••••••••">
        </label>
        <label style="display:flex;align-items:center;gap:.5rem;font-weight:500">
          <input type="checkbox" name="remember" value="1" style="width:auto"> مرا به خاطر بسپار
        </label>
        <button class="btn" type="submit">ورود به پنل</button>
      </form>
      <p class="login-gate-note">بازگشت به <a href="{{ url('/') }}">سایت اصلی</a></p>
    </div>
  </div>
</div>
@endsection
