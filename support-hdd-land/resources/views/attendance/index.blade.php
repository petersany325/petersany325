@extends('layouts.app')
@section('title', 'حضور و غیاب | '.shop_name())
@section('page_title', 'حضور و غیاب')
@section('window_title', 'ثبت ورود و خروج کارمند')

@section('content')
@php
  $open = (bool) ($state['open'] ?? false);
  $nextType = $open ? 'check_out' : 'check_in';
  $nextLabel = $open ? 'ثبت خروج از شرکت' : 'ثبت ورود به شرکت';
@endphp

<div class="emp-cartable">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>حضور و غیاب</h2>
            <p class="lead">ورود/خروج با سلفی لحظه‌ای یا رمز یک‌بارمصرف + GPS</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @if($canManage)
                <a class="btn btn-secondary" href="{{ route('attendance.manage') }}">مدیریت و گزارش</a>
                <a class="btn btn-ghost" href="{{ route('attendance.settings') }}">تنظیمات GPS</a>
            @endif
        </div>
    </div>

    @if(! $settings['enabled'])
        <div class="alert alert-error">سیستم حضور و غیاب توسط مدیر غیرفعال شده است.</div>
    @else
        <div class="emp-stat-row">
            <div class="emp-stat {{ $open ? 'tone-ok' : '' }}"><span>وضعیت امروز</span><strong>{{ $open ? 'داخل شرکت' : 'خارج / بدون ورود' }}</strong></div>
            <div class="emp-stat"><span>آخرین ثبت</span><strong>{{ $state['last']?->occurred_at?->format('H:i') ?: '—' }}</strong></div>
            <div class="emp-stat tone-sms"><span>روزهای حضور (بازه)</span><strong>{{ $presentDays }}</strong></div>
            <div class="emp-stat"><span>عکس مرجع</span><strong>{{ $profile?->hasReferencePhoto() ? 'ثبت شده' : 'ثبت نشده' }}</strong></div>
        </div>

        @if($settings['require_enrolled_photo'] && $settings['allow_selfie'] && ! $profile?->hasReferencePhoto())
            <div class="alert alert-error">عکس مرجع شما هنوز توسط <strong>ادمین</strong> ثبت نشده؛ برای سلفی ابتدا ادمین باید عکس اولیه را بگیرد. فعلاً در صورت فعال بودن می‌توانید از OTP استفاده کنید.</div>
        @endif

        <div class="panel" style="margin-top:12px;padding:14px;">
            <h3 style="margin:0 0 10px;">{{ $nextLabel }}</h3>
            <p class="muted" style="margin:0 0 12px;">موقعیت GPS از موبایل خوانده می‌شود. سلفی باید از دوربین جلو گرفته شود (نه گالری).</p>

            <form method="POST" action="{{ route('attendance.otp') }}" id="att-otp-form" style="margin-bottom:10px;">
                @csrf
                <input type="hidden" name="purpose" value="{{ $nextType }}">
                @if($settings['allow_otp'])
                    <button class="btn btn-secondary" type="submit">ارسال رمز یک‌بارمصرف SMS</button>
                @endif
            </form>

            <form method="POST" action="{{ route('attendance.punch') }}" enctype="multipart/form-data" id="att-punch-form" class="form-grid">
                @csrf
                <input type="hidden" name="type" value="{{ $nextType }}">
                <input type="hidden" name="latitude" id="att-lat" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="att-lng" value="{{ old('longitude') }}">
                <input type="hidden" name="accuracy_m" id="att-acc" value="{{ old('accuracy_m') }}">
                <input type="hidden" name="device_fingerprint" id="att-device" value="">

                <div>
                    <label>روش تأیید</label>
                    <select name="method" id="att-method" required>
                        @if($settings['allow_selfie'])
                            <option value="selfie" @selected(old('method', session('attendance_method')) === 'selfie')>سلفی لحظه‌ای</option>
                        @endif
                        @if($settings['allow_otp'])
                            <option value="otp" @selected(old('method', session('attendance_method')) === 'otp')>رمز یک‌بارمصرف</option>
                        @endif
                    </select>
                </div>

                <div id="att-otp-wrap">
                    <label>کد OTP</label>
                    <input type="text" name="otp_code" inputmode="numeric" maxlength="10" value="{{ old('otp_code') }}" placeholder="کد پیامک‌شده" dir="ltr">
                </div>

                <div id="att-photo-wrap">
                    <label>سلفی (دوربین جلو)</label>
                    <input type="file" name="photo" accept="image/*" capture="user">
                </div>

                <div>
                    <label>یادداشت (اختیاری)</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="500">
                </div>

                <div style="grid-column:1/-1;">
                    <div class="muted" id="att-gps-status">در حال دریافت موقعیت GPS…</div>
                    <button class="btn btn-primary" type="submit" id="att-submit" @disabled(! $settings['enabled'])>{{ $nextLabel }}</button>
                </div>
            </form>
        </div>
    @endif

    <div class="panel" style="margin-top:16px;padding:14px;">
        <h3 style="margin:0 0 8px;">روزهای حضور من</h3>
        <form method="GET" class="actions" style="margin-bottom:10px;gap:8px;flex-wrap:wrap;">
            <label>از <input type="date" name="from" value="{{ $from }}" dir="ltr"></label>
            <label>تا <input type="date" name="to" value="{{ $to }}" dir="ltr"></label>
            <button class="btn btn-secondary" type="submit">محاسبه</button>
        </form>
        <p>تعداد روزهای حضور در بازه: <strong>{{ $presentDays }}</strong> روز</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>ورود</th>
                        <th>خروج</th>
                        <th>تأخیر</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr>
                            <td dir="ltr">{{ $row['date'] }}</td>
                            <td dir="ltr">{{ $row['check_in'] ?: '—' }}</td>
                            <td dir="ltr">{{ $row['check_out'] ?: '—' }}</td>
                            <td>{{ $row['late'] ? 'تأخیر' : '—' }}</td>
                            <td>{{ $row['flagged'] ? 'مشکوک' : 'عادی' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">در این بازه روز حضوری ثبت نشده.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel" style="margin-top:16px;padding:14px;">
        <h3 style="margin:0 0 8px;">آخرین ثبت‌ها</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>زمان</th>
                        <th>نوع</th>
                        <th>روش</th>
                        <th>فاصله</th>
                        <th>وضعیت</th>
                        <th>عکس</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $ev)
                        <tr>
                            <td dir="ltr">{{ $ev->occurred_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $ev->typeLabel() }}</td>
                            <td>{{ $ev->methodLabel() }}</td>
                            <td dir="ltr">{{ $ev->distance_m !== null ? $ev->distance_m.' m' : '—' }}</td>
                            <td>{{ $ev->statusLabel() }}@if($ev->flag_reason)<div class="muted" style="font-size:11px;">{{ $ev->flag_reason }}</div>@endif</td>
                            <td>
                                @if($ev->photo_path)
                                    <a href="{{ route('attendance.punch-photo', $ev) }}" target="_blank">مشاهده</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">هنوز ثبتی نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
  const lat = document.getElementById('att-lat');
  const lng = document.getElementById('att-lng');
  const acc = document.getElementById('att-acc');
  const status = document.getElementById('att-gps-status');
  const method = document.getElementById('att-method');
  const otpWrap = document.getElementById('att-otp-wrap');
  const photoWrap = document.getElementById('att-photo-wrap');
  const device = document.getElementById('att-device');

  function syncMethod() {
    const m = method ? method.value : 'selfie';
    if (otpWrap) otpWrap.style.display = m === 'otp' ? '' : 'none';
    if (photoWrap) photoWrap.style.display = m === 'selfie' ? '' : 'none';
  }
  if (method) method.addEventListener('change', syncMethod);
  syncMethod();

  try {
    const key = 'att_device_fp';
    let fp = localStorage.getItem(key);
    if (!fp) {
      fp = 'd_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36);
      localStorage.setItem(key, fp);
    }
    if (device) device.value = fp;
  } catch (e) {}

  if (!navigator.geolocation) {
    if (status) status.textContent = 'مرورگر GPS را پشتیبانی نمی‌کند.';
    return;
  }
  navigator.geolocation.getCurrentPosition(function (pos) {
    if (lat) lat.value = pos.coords.latitude.toFixed(7);
    if (lng) lng.value = pos.coords.longitude.toFixed(7);
    if (acc) acc.value = pos.coords.accuracy != null ? Math.round(pos.coords.accuracy) : '';
    if (status) {
      status.textContent = 'موقعیت آماده است — دقت حدود ' + Math.round(pos.coords.accuracy || 0) + ' متر'
        + ({{ $settings['require_inside_geofence'] ? 'true' : 'false' }} ? ' | شعاع مجاز شرکت: {{ $settings['geofence_radius_m'] }} متر' : '');
    }
  }, function (err) {
    if (status) status.textContent = 'خطا در دریافت GPS: ' + (err && err.message ? err.message : 'دسترسی مکان را فعال کنید');
  }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
})();
</script>
@endsection
