@extends('layouts.storefront')
@section('title','ورود')
@section('content')
<style>
  .login-shell{display:grid;grid-template-columns:1.05fr .95fr;width:min(860px,calc(100% - 28px));margin:34px auto 60px;overflow:hidden;border:1px solid #e2e8f0;border-radius:26px;background:#fff;box-shadow:0 24px 65px rgba(15,23,42,.12)}
  .login-visual{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;min-height:520px;padding:38px;color:#fff;background:linear-gradient(145deg,#172554 0%,#1d4ed8 58%,#38bdf8 100%)}
  .login-visual:before,.login-visual:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.1)}
  .login-visual:before{width:240px;height:240px;left:-90px;top:-100px}.login-visual:after{width:170px;height:170px;right:-65px;bottom:-70px}
  .login-brand,.login-benefits{position:relative;z-index:1}.login-mark{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(255,255,255,.12);font-size:11px;font-weight:800}
  .login-brand h1{margin:24px 0 8px;color:#fff;font-size:30px;line-height:1.55}.login-brand p{margin:0;color:rgba(255,255,255,.78);font-size:13px;line-height:2}
  .login-benefits{display:grid;gap:10px}.login-benefits span{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700}.login-benefits i{display:grid;place-items:center;width:25px;height:25px;border-radius:9px;background:rgba(255,255,255,.15);font-style:normal}
  .login-panel{padding:40px 38px}.login-panel h2{margin:0;color:#0f172a;font-size:24px;font-weight:900}.login-panel .lead{margin:6px 0 25px;color:#64748b;font-size:12.5px}
  .login-panel label{display:block;margin:0 0 16px;color:#334155;font-size:12px;font-weight:800}.login-panel input[type=text],.login-panel input[type=password]{width:100%;height:50px;margin-top:7px;padding:0 14px;border:1px solid #dbe3ee;border-radius:13px;background:#f8fafc;font-size:13px;transition:.2s}
  .login-panel input:focus{border-color:#3b82f6;background:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.11);outline:0}
  .login-password{position:relative}.login-password input{padding-left:48px!important}.login-eye{position:absolute;left:9px;bottom:8px;width:34px;height:34px;border:0;border-radius:9px;color:#475569;background:#eaf0f8;cursor:pointer}
  .login-options{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:-2px 0 18px}.login-remember{display:flex!important;align-items:center;gap:7px;margin:0!important}.login-remember input{width:17px;height:17px;accent-color:#2563eb}.login-forgot{color:#1d4ed8;font-size:11.5px;font-weight:800;text-decoration:none}
  .login-submit{width:100%;height:52px;border:0!important;border-radius:14px!important;background:linear-gradient(135deg,#ea580c,#f97316)!important;box-shadow:0 12px 26px rgba(234,88,12,.23);font-size:15px!important;font-weight:900!important}
  .login-links{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:15px}.login-links a{display:flex;align-items:center;justify-content:center;min-height:42px;padding:8px;border:1px solid #e2e8f0;border-radius:11px;color:#334155;background:#f8fafc;font-size:11.5px;font-weight:800;text-decoration:none}.login-links a:hover{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.login-links a.login-sms{grid-column:1/-1;border-color:#fdba74;color:#c2410c;background:linear-gradient(180deg,#fff7ed,#ffedd5)}.login-links a.login-sms:hover{border-color:#fb923c;color:#9a3412;background:#fff7ed}
  .login-staff{margin:17px 0 0;padding:10px 12px;border-radius:11px;color:#64748b;background:#f8fafc;font-size:10.5px;line-height:1.8}.login-panel .alert{margin:0 0 16px}
  @media(max-width:720px){.login-shell{grid-template-columns:1fr;margin:14px auto 38px;border-radius:19px}.login-visual{min-height:auto;padding:25px 21px}.login-brand h1{margin:15px 0 5px;font-size:23px}.login-benefits{display:none}.login-panel{padding:25px 20px 28px}.login-links{grid-template-columns:1fr}}
</style>
<main class="login-shell">
  <section class="login-visual">
    <div class="login-brand">
      <span class="login-mark">✓ ورود امن سرزمین هارد</span>
      <h1>خوش آمدید</h1>
      <p>سفارش‌ها، فاکتورها، کیف پول و خدمات پشتیبانی شما در یک کارتابل هوشمند و یکپارچه.</p>
    </div>
    <div class="login-benefits">
      <span><i>▣</i> مشاهده و پیگیری سفارش‌ها</span>
      <span><i>◈</i> مدیریت کیف پول و فاکتورها</span>
      <span><i>✦</i> پشتیبانی و گفت‌وگوی هوشمند</span>
    </div>
  </section>
  <section class="login-panel">
    <h2>ورود به حساب</h2>
    <p class="lead">با موبایل، نام کاربری یا ایمیل وارد شوید.</p>
    @auth
      @if(auth()->user()->isAdmin())
        <div class="alert alert-success">شما به‌عنوان مدیر وارد هستید. <a href="{{ url('/admin') }}">ورود به پنل ادمین</a></div>
      @endif
    @endauth
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="post" action="{{ url('/login') }}">@csrf
      <label>موبایل، نام کاربری یا ایمیل
        <input type="text" name="login" value="{{ old('login') }}" required placeholder="09... یا username یا name@email.com" autocomplete="username">
      </label>
      <label class="login-password">رمز عبور
        <input type="password" name="password" required autocomplete="current-password">
        <button class="login-eye" type="button" aria-label="نمایش رمز">◉</button>
      </label>
      <div class="login-options">
        <label class="login-remember"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
        <a class="login-forgot" href="{{ url('/forgot-password') }}">رمز را فراموش کرده‌اید؟</a>
      </div>
      <button class="btn btn-primary login-submit" type="submit">ورود به کارتابل</button>
      <div class="login-links">
        <a class="login-sms" href="{{ route('login.sms') }}">ورود با اس ام اس</a>
        <a href="{{ url('/register') }}">ایجاد حساب جدید</a>
        <a href="{{ url('/track-order') }}">پیگیری سفارش مهمان</a>
      </div>
      <p class="login-staff">ورود کارمندان فقط از مسیر اختصاصی و امن پنل مدیریت انجام می‌شود.</p>
    </form>
  </section>
</main>
<script>
(function(){var b=document.querySelector('.login-eye'),i=document.querySelector('input[name="password"]');if(!b||!i)return;b.addEventListener('click',function(){var s=i.type==='text';i.type=s?'password':'text';b.textContent=s?'◉':'⊘';b.setAttribute('aria-label',s?'نمایش رمز':'مخفی کردن رمز')})})();
</script>
@endsection
