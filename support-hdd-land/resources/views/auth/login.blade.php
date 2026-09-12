@extends('layouts.app')

@section('title', 'ورود | '.shop_name())

@section('content')
@php
    $otpFirst = session('otp_sent') || old('phone') || request()->boolean('otp');
    $isInternLogin = request()->boolean('intern');
@endphp
<div class="login-page">
    <div class="panel login-card login-card-modern">
        <div class="brand-center">
            <img class="brand-logo-lg" src="{{ shop_logo_url('main') }}" alt="{{ shop_name() }}" width="96" height="96">
            <h1>{{ shop_name() }}</h1>
            <p>{{ $isInternLogin ? 'ورود کارآموز — پرتال خدمات و دفتر روز' : 'ورود کارمند — موبایل و کامپیوتر' }}</p>
        </div>
        @include('partials.flash')

        <p class="hint" data-device-login-hint style="text-align:center;margin:0 0 10px;">تشخیص خودکار دستگاه…</p>

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
                <p class="hint" style="margin-top:8px;">مناسب کامپیوتر. اگر رمز ندارید از تب «موبایل / SMS» وارد شوید.</p>
                <div class="actions">
                    <button class="btn btn-primary" type="submit" style="width:100%;">ورود با رمز</button>
                </div>
            </form>
        </div>

        <div id="tab-otp" class="login-pane {{ $otpFirst ? '' : 'hidden' }}">
            @unless(session('otp_sent'))
                <form method="POST" action="{{ route('login.otp.send') }}">
                    @csrf
                    <p class="hint" style="margin-top:0;">مناسب وب‌سرویس موبایل. شماره ثبت‌شده در کارتابل کارمند را وارد کنید.</p>
                    <div class="form-grid" style="grid-template-columns:1fr;">
                        <div>
                            <label>شماره موبایل</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxxx" required {{ $otpFirst ? 'autofocus' : '' }} dir="ltr" style="text-align:left;" inputmode="tel" autocomplete="tel">
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
                            <input type="text" name="phone" value="{{ session('otp_phone') }}" required dir="ltr" style="text-align:left;" inputmode="tel">
                        </div>
                        <div>
                            <label>کد تأیید پیامک</label>
                            <input type="text" name="code" inputmode="numeric" required autofocus maxlength="6" dir="ltr" style="text-align:left;letter-spacing:0.3em;font-size:1.2rem;" autocomplete="one-time-code">
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
    try {
        var forced = localStorage.getItem('staff_ui_mode');
        var mobile = (forced === 'mobile') || ((forced !== 'desktop') && window.matchMedia('(max-width: 900px)').matches);
        var otpForced = {{ $otpFirst ? 'true' : 'false' }};
        if (mobile && !otpForced) {
            var otpBtn = document.querySelector('[data-login-tab="otp"]');
            if (otpBtn) otpBtn.click();
        }
        var hint = document.querySelector('[data-device-login-hint]');
        if (hint) {
            hint.textContent = mobile
                ? 'موبایل تشخیص داده شد — ورود پیامکی پیشنهاد می‌شود.'
                : 'کامپیوتر تشخیص داده شد — ورود با رمز یا SMS.';
        }
    } catch (e) {}
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            window.location.reload();
        }
    });
})();
</script>
@endsection
