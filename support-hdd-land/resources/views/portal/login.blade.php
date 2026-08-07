@extends('layouts.portal')
@section('title', 'ورود مشتری | سرزمین هارد')
@section('body_class', 'portal-body portal-auth')

@section('content')
<div class="p-auth">
    <div class="p-auth-orb orb-a"></div>
    <div class="p-auth-orb orb-b"></div>
    <div class="p-auth-card">
        <div class="p-brand">
            <div class="p-logo">▣</div>
            <div>
                <strong>سرزمین هارد</strong>
                <span>کارتابل مشتری</span>
            </div>
        </div>
        <p style="text-align:center;margin:0 0 10px;">
            <a href="{{ route('gate') }}" style="font-size:12px;font-weight:700;color:#0f766e;">→ بازگشت به انتخاب ورود</a>
        </p>
        <h1>ورود با موبایل</h1>
        <p class="p-lead">شماره موبایل ثبت‌شده در پذیرش را وارد کنید؛ کد پیامک برایتان می‌آید.</p>

        @if(session('success'))
            <div class="p-alert ok">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="p-alert err">{{ $errors->first() }}</div>
        @endif
        @if(session('debug_otp'))
            <div class="p-alert dbg">کد تست: <b dir="ltr">{{ session('debug_otp') }}</b></div>
        @endif

        @if(session('otp_sent'))
            <form method="POST" action="{{ route('portal.otp.verify') }}" class="p-form" autocomplete="one-time-code">
                @csrf
                <input type="hidden" name="phone" value="{{ session('otp_phone') }}">
                <label>کد ۶ رقمی پیامک
                    <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                           placeholder="------" required autofocus dir="ltr" class="p-otp-input">
                </label>
                <button class="p-btn primary" type="submit">تأیید و ورود به کارتابل</button>
                <a class="p-btn ghost" href="{{ route('portal.login') }}">تغییر شماره</a>
            </form>
            <p class="p-hint" dir="ltr">{{ session('otp_phone') }}</p>
        @else
            <form method="POST" action="{{ route('portal.otp.send') }}" class="p-form">
                @csrf
                <label>شماره موبایل
                    <input type="tel" name="phone" value="{{ old('phone') }}"
                           placeholder="09xxxxxxxxx" required autofocus dir="ltr" inputmode="tel" autocomplete="tel">
                </label>
                <button class="p-btn primary" type="submit">دریافت کد پیامک</button>
            </form>
        @endif
    </div>
</div>
<script>
window.addEventListener('pageshow', function (e) {
    if (e.persisted) window.location.reload();
});
</script>
@endsection
