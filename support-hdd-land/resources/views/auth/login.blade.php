@extends('layouts.app')

@section('title', 'ورود | سرزمین هارد')

@section('content')
@php $otpFirst = session('otp_sent') || old('phone') || request()->boolean('otp'); @endphp
<div class="login-page">
    <div class="panel login-card login-card-modern">
        <div class="brand-center">
            <h1>سرزمین هارد</h1>
            <p>ورود کارمند به کارتابل مدیریت</p>
        </div>
        @include('partials.flash')

        <p style="text-align:center;margin:0 0 12px;">
            <a href="{{ route('gate') }}" style="font-size:12px;font-weight:700;color:#0f766e;">→ بازگشت به انتخاب ورود</a>
        </p>
        <div class="login-tabs">
            <button type="button" class="login-tab {{ $otpFirst ? 'is-active' : '' }}" data-login-tab="otp">موبایل / SMS</button>
            <button type="button" class="login-tab {{ $otpFirst ? '' : 'is-active' }}" data-login-tab="pass">رمز عبور</button>
        </div>

        <div id="tab-pass" class="login-pane {{ $otpFirst ? 'hidden' : '' }}">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>ایمیل یا موبایل</label>
                        <input type="text" name="login" value="{{ old('login') }}" placeholder="email@example.com یا 09..." required {{ $otpFirst ? '' : 'autofocus' }} autocomplete="username">
                    </div>
                    <div>
                        <label>رمز عبور</label>
                        <input type="password" name="password" required autocomplete="current-password">
                    </div>
                </div>
                <p class="hint" style="margin-top:8px;">اگر رمز را فراموش کرده‌اید از تب «موبایل / SMS» با شماره ثبت‌شده وارد شوید.</p>
                <div class="actions">
                    <button class="btn btn-primary" type="submit" style="width:100%;">ورود با رمز</button>
                </div>
            </form>
        </div>

        <div id="tab-otp" class="login-pane {{ $otpFirst ? '' : 'hidden' }}">
            @unless(session('otp_sent'))
                <form method="POST" action="{{ route('login.otp.send') }}">
                    @csrf
                    <p class="hint" style="margin-top:0;">شماره موبایل ثبت‌شده در کارتابل کارمند را وارد کنید. کد تأیید پیامک ارسال می‌شود.</p>
                    <div class="form-grid" style="grid-template-columns:1fr;">
                        <div>
                            <label>شماره موبایل</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxxx" required {{ $otpFirst ? 'autofocus' : '' }} dir="ltr" style="text-align:left;">
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" type="submit" style="width:100%;">ارسال کد تأیید SMS</button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('login.otp.verify') }}">
                    @csrf
                    <p class="hint" style="margin-top:0;">کد ارسال‌شده به موبایل را وارد کنید.</p>
                    <div class="form-grid" style="grid-template-columns:1fr;">
                        <div>
                            <label>شماره موبایل</label>
                            <input type="text" name="phone" value="{{ session('otp_phone') }}" required dir="ltr" style="text-align:left;">
                        </div>
                        <div>
                            <label>کد تأیید پیامک</label>
                            <input type="text" name="code" inputmode="numeric" required autofocus maxlength="6" dir="ltr" style="text-align:left;letter-spacing:0.3em;font-size:1.2rem;">
                        </div>
                    </div>
                    @if(session('debug_otp'))
                        <p class="muted">کد توسعه: {{ session('debug_otp') }}</p>
                    @endif
                    <div class="actions">
                        <button class="btn btn-primary" type="submit" style="width:100%;">تأیید و ورود به کارتابل</button>
                    </div>
                </form>
            @endunless
        </div>
    </div>
</div>
<script>
(function () {
    document.querySelectorAll('[data-login-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-login-tab');
            document.getElementById('tab-pass').classList.toggle('hidden', name !== 'pass');
            document.getElementById('tab-otp').classList.toggle('hidden', name !== 'otp');
            document.querySelectorAll('[data-login-tab]').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
            });
        });
    });
    // Avoid submitting a stale CSRF token from browser back-forward cache
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            window.location.reload();
        }
    });
})();
</script>
@endsection
