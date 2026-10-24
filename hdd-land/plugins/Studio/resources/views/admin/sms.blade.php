@extends('layouts.admin')
@section('title', 'پیامک ورود سایت')
@section('content')
@php
  $s = $sms ?? [];
  $ready = ! empty($s['ready']);
@endphp
<style>
.ag-sms h1{margin:0 0 .35rem;font-size:1.35rem}
.ag-sms .hint{color:#64748b;font-size:.9rem;margin:0 0 1rem;line-height:1.7}
.ag-sms .card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem;margin-bottom:.85rem}
.ag-sms .card h2{margin:0 0 .75rem;font-size:1rem}
.ag-sms label{display:grid;gap:.28rem;font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:.7rem}
.ag-sms input{width:100%;border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit;background:#f8fafc}
.ag-sms .row2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:800px){.ag-sms .row2{grid-template-columns:1fr}}
.ag-sms .badge{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.28rem .7rem;font-size:.8rem;font-weight:700}
.ag-sms .ok{background:#ecfdf5;color:#047857}
.ag-sms .bad{background:#fff1f2;color:#be123c}
.ag-sms .actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem}
</style>
<div class="ag-sms">
  <h1>پیامک ورود سایت — نیازپرداز</h1>
  <p class="hint">
    قبض‌های تعمیرگاه از پنل نیازپرداز روی support ارسال می‌شوند.
    ورود کاربر و کارمند روی سایت اصلی جدا بود و به همان پنل وصل نبود.
    اینجا همان یوزر، رمز و خط پنل را برای کد ورود سایت ذخیره کنید.
    دیتابیس قبض دست نمی‌خورد.
  </p>

  @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem">{{ session('error') }}</div>
  @endif

  <div class="card">
    <h2>وضعیت اتصال</h2>
    <p>
      @if($ready)
        <span class="badge ok">وصل است — کد ورود از پنل نیازپرداز می‌رود</span>
      @else
        <span class="badge bad">وصل نیست — یوزر / رمز / خط را ذخیره کنید</span>
      @endif
    </p>
    @if(!empty($s['source']) && $s['source'] !== 'missing')
      <p class="hint" style="margin:0">منبع فعلی تنظیم: {{ $s['source'] === 'studio' ? 'همین صفحه' : ($s['source'] === 'env' ? 'فایل محیطی سرور' : 'تنظیمات مشتریان') }}</p>
    @endif
  </div>

  <form method="post" action="{{ url('/admin/studio-sms') }}" autocomplete="off">
    @csrf
    <div class="card">
      <h2>حساب پنل نیازپرداز</h2>
      <div class="row2">
        <label>نام کاربری پنل
          <input name="username" value="{{ old('username', $s['username'] ?? '') }}" required autocomplete="off">
        </label>
        <label>شماره خط فرستنده
          <input name="from" value="{{ old('from', $s['from'] ?? '') }}" required placeholder="مثلاً 3000... یا 5000...">
        </label>
      </div>
      <label>رمز پنل
        <input name="password" type="password" value="" autocomplete="new-password" placeholder="{{ !empty($s['has_password']) ? 'رمز ذخیره شده — برای تغییر رمز جدید بنویسید' : 'رمز ورود پنل نیازپرداز' }}">
      </label>
      <label>API Key (اختیاری — اگر پنل داد)
        <input name="api_key" type="password" value="" autocomplete="off" placeholder="{{ !empty($s['has_api_key']) ? 'کلید ذخیره شده — برای تغییر کلید جدید بنویسید' : 'اگر ندارید خالی بگذارید' }}">
      </label>
      <p class="hint">ارسال اول از آدرس پنل <code>panel.niazpardaz-sms.com</code> می‌رود؛ همان مسیری که قبض‌ها استفاده می‌کنند.</p>
    </div>
    <div class="actions">
      <button class="btn btn-primary" type="submit">ذخیره و وصل به ورود سایت</button>
      <a class="btn" href="{{ url('/auth/customer') }}" target="_blank" rel="noopener">صفحه ورود کاربر</a>
      <a class="btn" href="{{ url('/auth/staff') }}" target="_blank" rel="noopener">صفحه ورود کارمند</a>
    </div>
  </form>
</div>
@endsection
