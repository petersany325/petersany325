@extends('layouts.install')

@section('title', 'نصب کامل شد')

@section('content')
  <div class="card">
    <h1>نصب با موفقیت انجام شد</h1>
    <p class="lead">این اطلاعات ورود ادمین را همین حالا ذخیره کنید. رمز فقط یک‌بار نمایش داده می‌شود.</p>

    <div class="creds">
      <div>آدرس پنل: <code>{{ $adminUrl }}</code></div>
      <div style="margin-top:.6rem">ایمیل: <code>{{ $adminEmail }}</code></div>
      <div style="margin-top:.6rem">رمز عبور: <code>{{ $adminPassword }}</code></div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <a class="btn" href="{{ $adminUrl }}">ورود به پنل ادمین</a>
      <a class="btn" style="background:#0a1628;color:#fff" href="{{ $siteUrl }}">مشاهده سایت</a>
    </div>
  </div>
@endsection
