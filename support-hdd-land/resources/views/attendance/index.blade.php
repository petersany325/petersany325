@extends('layouts.app')
@section('title', 'حضور و غیاب | '.shop_name())
@section('page_title', 'حضور و غیاب')
@section('window_title', 'ثبت ورود و خروج کارمند')

@section('content')
@php
  $open = (bool) ($state['open'] ?? false);
  $nextType = $open ? 'check_out' : 'check_in';
  $nextLabel = $open ? 'ثبت خروج از شرکت' : 'ثبت ورود به شرکت';
  $selfieReady = $profile?->isSelfieApproved() && ($settings['allow_selfie'] ?? false);
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
            <p class="lead">ورود/خروج روزانه با تأیید عکس مرجع یا لینک SMS + GPS — هر روز از نیمه‌شب ریست می‌شود</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @if($canManage)
                <a class="btn btn-secondary" href="{{ route('attendance.manage') }}">مدیریت و گزارش</a>
                <a class="btn btn-ghost" href="{{ route('attendance.settings') }}">تنظیمات GPS</a>
            @endif
        </div>
    </div>

    @if(! $settings['enabled'])
        <div class="alert alert-error">سیستم حضور و غیاب توسط مدیر برای همه غیرفعال شده است.</div>
    @elseif(! ($accessAllowed ?? true))
        <div class="alert alert-error">
            <strong>دسترسی حضور و غیاب شما غیرفعال است.</strong>
            <div style="margin-top:6px;">مدیر دسترسی فردی شما را بسته است؛ فعلاً نمی‌توانید ورود/خروج ثبت کنید. در صورت نیاز با مدیر تماس بگیرید.</div>
        </div>
        <div class="emp-stat-row">
            <div class="emp-stat"><span>وضعیت دسترسی</span><strong>غیرفعال</strong></div>
            <div class="emp-stat"><span>وضعیت سلفی</span><strong>{{ $profile?->selfieStatusLabel() ?? 'ثبت نشده' }}</strong></div>
            <div class="emp-stat tone-sms"><span>روزهای حضور (بازه)</span><strong>{{ $presentDays }}</strong></div>
        </div>
    @else
        <div class="emp-stat-row">
            <div class="emp-stat {{ $open ? 'tone-ok' : '' }}"><span>وضعیت امروز</span><strong>{{ $open ? 'داخل شرکت' : 'خارج / بدون ورود' }}</strong></div>
            <div class="emp-stat"><span>آخرین ثبت</span><strong>{{ $state['last']?->occurred_at?->format('H:i') ?: '—' }}</strong></div>
            <div class="emp-stat tone-sms"><span>روزهای حضور (بازه)</span><strong>{{ $presentDays }}</strong></div>
            <div class="emp-stat"><span>وضعیت سلفی</span><strong>{{ $profile?->selfieStatusLabel() ?? 'ثبت نشده' }}</strong></div>
            <div class="emp-stat tone-ok"><span>دسترسی</span><strong>فعال</strong></div>
        </div>

        @if($profile?->isSelfiePending())
            <div class="alert alert-error">سلفی شما <strong>در انتظار تأیید ادمین</strong> است. تا تأیید، ورود خودکار با سلفی فعال نیست.@if($settings['allow_otp']) فعلاً می‌توانید از OTP استفاده کنید.@endif</div>
        @elseif($profile?->isSelfieRejected())
            <div class="alert alert-error">
                سلفی رد شده{{ $profile->selfie_reject_reason ? ' — '.$profile->selfie_reject_reason : '' }}.
                <a href="{{ route('attendance.onboard') }}" class="btn btn-secondary" style="margin-right:8px;">ثبت مجدد سلفی</a>
            </div>
        @elseif(! $profile?->hasReferencePhoto())
            <div class="alert alert-error">
                هنوز سلفی مرجع ثبت نکرده‌اید.
                <a href="{{ route('attendance.onboard') }}" class="btn btn-secondary" style="margin-right:8px;">ثبت اولیه</a>
            </div>
        @endif

        <div class="panel" style="margin-top:12px;padding:14px;">
            <h3 style="margin:0 0 6px;">{{ $nextLabel }}</h3>
            <p class="muted" style="margin:0 0 12px;">وضعیت ورود/خروج هر روز ساعت ۰۰:۰۰ ریست می‌شود.</p>

            @if($selfieReady)
                <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;margin-bottom:14px;padding:12px;border:1px solid #dbeafe;background:#f8fbff;border-radius:10px;">
                    <img src="{{ route('attendance.my-photo') }}" alt="عکس تأییدشده" style="width:120px;height:120px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#111;">
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:700;margin-bottom:6px;">عکس تأییدشده شما</div>
                        <p class="muted" style="margin:0 0 10px;">همین عکس را ببینید و برای {{ $nextLabel }} تأیید کنید (همراه GPS).</p>
                        <form method="POST" action="{{ route('attendance.punch') }}" id="att-confirm-form">
                            @csrf
                            <input type="hidden" name="type" value="{{ $nextType }}">
                            <input type="hidden" name="method" value="photo_confirm">
                            <input type="hidden" name="latitude" class="att-lat-sync" value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" class="att-lng-sync" value="{{ old('longitude') }}">
                            <input type="hidden" name="accuracy_m" class="att-acc-sync" value="{{ old('accuracy_m') }}">
                            <input type="hidden" name="device_fingerprint" class="att-device-sync" value="">
                            <input type="hidden" name="photo_confirmed" id="att-photo-confirmed" value="0">
                            <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;margin-bottom:10px;">
                                <input type="checkbox" id="att-photo-check">
                                <span>این عکس من است و خودم {{ $nextLabel }} را ثبت می‌کنم.</span>
                            </label>
                            <button class="btn btn-primary" type="submit" id="att-confirm-btn" disabled>{{ $nextLabel }} با تأیید عکس</button>
                        </form>
                    </div>
                </div>
            @endif

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <form method="POST" action="{{ route('attendance.link-sms') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="purpose" value="{{ $nextType }}">
                    <button class="btn btn-secondary" type="submit">ارسال لینک {{ $open ? 'خروج' : 'ورود' }} با SMS</button>
                </form>
                @if($settings['allow_otp'])
                <form method="POST" action="{{ route('attendance.otp') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="purpose" value="{{ $nextType }}">
                    <button class="btn btn-ghost" type="submit">ارسال کد OTP</button>
                </form>
                @endif
            </div>

            <form method="POST" action="{{ route('attendance.punch') }}" enctype="multipart/form-data" id="att-punch-form" class="form-grid">
                @csrf
                <input type="hidden" name="type" value="{{ $nextType }}">
                <input type="hidden" name="method" id="att-method" value="{{ $selfieReady ? 'photo_confirm' : ($settings['allow_otp'] ? 'otp' : 'selfie') }}">
                <input type="hidden" name="latitude" id="att-lat" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="att-lng" value="{{ old('longitude') }}">
                <input type="hidden" name="accuracy_m" id="att-acc" value="{{ old('accuracy_m') }}">
                <input type="hidden" name="device_fingerprint" id="att-device" value="">
                <input type="hidden" name="face_detected" id="att-face" value="0">
                <input type="hidden" name="photo_confirmed" value="0">
                <input type="file" name="photo" id="att-photo" accept="image/*" capture="user" style="display:none;">

                @if($settings['allow_otp'])
                    <div id="att-otp-wrap" style="grid-column:1/-1;">
                        <label>کد OTP (اختیاری)</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <input type="text" name="otp_code" id="att-otp-code" inputmode="numeric" maxlength="10" value="{{ old('otp_code') }}" placeholder="کد پیامک‌شده" dir="ltr" style="max-width:160px;">
                            <button class="btn btn-ghost" type="button" id="att-otp-punch">{{ $nextLabel }} با OTP</button>
                        </div>
                    </div>
                @endif

                @if($selfieReady)
                <details style="grid-column:1/-1;margin-top:4px;">
                    <summary class="muted">روش پیشرفته: سلفی لحظه‌ای با دوربین</summary>
                    <div style="margin-top:10px;">
                        <video id="att-live-cam" playsinline autoplay muted style="width:100%;max-width:320px;border-radius:10px;background:#111;transform:scaleX(-1);"></video>
                        <div style="margin:10px 0;display:flex;gap:8px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-secondary" id="att-open-cam">روشن کردن دوربین</button>
                            <button type="button" class="btn btn-primary" id="att-auto-punch" disabled>{{ $nextLabel }} با سلفی لحظه‌ای</button>
                        </div>
                        <div class="muted" id="att-face-live">صورت را روبه‌روی دوربین بگیرید.</div>
                    </div>
                </details>
                @endif

                <div style="grid-column:1/-1;">
                    <div class="muted" id="att-gps-status">در حال دریافت موقعیت GPS…</div>
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

@if($selfieReady)
@include('partials.attendance-face')
@endif
<script>
(function () {
  var lat = document.getElementById('att-lat');
  var lng = document.getElementById('att-lng');
  var acc = document.getElementById('att-acc');
  var status = document.getElementById('att-gps-status');
  var method = document.getElementById('att-method');
  var device = document.getElementById('att-device');
  var face = document.getElementById('att-face');
  var photo = document.getElementById('att-photo');
  var form = document.getElementById('att-punch-form');
  var liveStatus = document.getElementById('att-face-live');
  var openBtn = document.getElementById('att-open-cam');
  var punchBtn = document.getElementById('att-auto-punch');
  var video = document.getElementById('att-live-cam');
  var stream = null;
  var detecting = false;
  var submitted = false;
  var selfieReady = {{ $selfieReady ? 'true' : 'false' }};
  var fp = '';

  try {
    var key = 'att_device_fp';
    fp = localStorage.getItem(key);
    if (!fp) {
      fp = 'd_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36);
      localStorage.setItem(key, fp);
    }
    if (device) device.value = fp;
    document.querySelectorAll('.att-device-sync').forEach(function (el) { el.value = fp; });
  } catch (e) {}

  function syncGeo(la, ln, ac) {
    if (lat) lat.value = la;
    if (lng) lng.value = ln;
    if (acc) acc.value = ac;
    document.querySelectorAll('.att-lat-sync').forEach(function (el) { el.value = la; });
    document.querySelectorAll('.att-lng-sync').forEach(function (el) { el.value = ln; });
    document.querySelectorAll('.att-acc-sync').forEach(function (el) { el.value = ac; });
  }

  var photoCheck = document.getElementById('att-photo-check');
  var photoConfirmed = document.getElementById('att-photo-confirmed');
  var confirmBtn = document.getElementById('att-confirm-btn');
  var confirmForm = document.getElementById('att-confirm-form');
  function syncConfirmBtn() {
    if (!confirmBtn) return;
    var ok = photoCheck && photoCheck.checked && lat && lat.value && lng && lng.value;
    confirmBtn.disabled = !ok;
    if (photoConfirmed) photoConfirmed.value = (photoCheck && photoCheck.checked) ? '1' : '0';
  }
  if (photoCheck) photoCheck.addEventListener('change', syncConfirmBtn);
  if (confirmForm) {
    confirmForm.addEventListener('submit', function (e) {
      if (!photoCheck || !photoCheck.checked) {
        e.preventDefault();
        alert('ابتدا عکس را تأیید کنید.');
        return;
      }
      if (!lat.value || !lng.value) {
        e.preventDefault();
        alert('GPS هنوز آماده نیست.');
      }
    });
  }

  if (!navigator.geolocation) {
    if (status) status.textContent = 'مرورگر GPS را پشتیبانی نمی‌کند.';
  } else {
    navigator.geolocation.getCurrentPosition(function (pos) {
      var la = pos.coords.latitude.toFixed(7);
      var ln = pos.coords.longitude.toFixed(7);
      var ac = pos.coords.accuracy != null ? Math.round(pos.coords.accuracy) : '';
      syncGeo(la, ln, ac);
      syncConfirmBtn();
      if (status) {
        status.textContent = 'موقعیت آماده است — دقت حدود ' + Math.round(pos.coords.accuracy || 0) + ' متر'
          + ({{ $settings['require_inside_geofence'] ? 'true' : 'false' }} ? ' | شعاع مجاز شرکت: {{ $settings['geofence_radius_m'] }} متر' : '');
      }
    }, function (err) {
      if (status) status.textContent = 'خطا در دریافت GPS: ' + (err && err.message ? err.message : 'دسترسی مکان را فعال کنید');
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  }

  var otpPunch = document.getElementById('att-otp-punch');
  if (otpPunch && form && method) {
    otpPunch.addEventListener('click', function () {
      method.value = 'otp';
      if (face) face.value = '0';
      form.submit();
    });
  }

  if (!selfieReady || !openBtn || !punchBtn || !video || typeof AttFace === 'undefined') return;

  function stopCam() {
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
  }

  openBtn.addEventListener('click', function () {
    if (liveStatus) liveStatus.textContent = 'درخواست دسترسی دوربین…';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
      .then(function (s) {
        stream = s;
        video.srcObject = s;
        punchBtn.disabled = false;
        if (liveStatus) liveStatus.textContent = 'صورت را روبه‌رو بگیرید؛ تشخیص خودکار شروع می‌شود…';
        AttFace.load().then(function () {
          loopDetect();
        }).catch(function (e) {
          if (liveStatus) liveStatus.textContent = 'خطا در لود تشخیص چهره: ' + (e && e.message ? e.message : '');
        });
      })
      .catch(function (err) {
        if (liveStatus) liveStatus.textContent = 'دوربین در دسترس نیست: ' + (err && err.message ? err.message : '');
      });
  });

  function loopDetect() {
    if (!stream || submitted || detecting) return;
    detecting = true;
    AttFace.detectFromVideo(video).then(function (res) {
      detecting = false;
      if (submitted) return;
      if (res && res.ok) {
        if (liveStatus) liveStatus.textContent = 'چهره تشخیص داده شد ✓ — در حال ثبت خودکار…';
        punchBtn.disabled = true;
        doAutoPunch();
      } else {
        if (liveStatus) liveStatus.textContent = 'چهره پیدا نشد — صورت را روبه‌روی دوربین نگه دارید…';
        setTimeout(loopDetect, 900);
      }
    }).catch(function () {
      detecting = false;
      if (!submitted) setTimeout(loopDetect, 1200);
    });
  }

  function doAutoPunch() {
    if (submitted) return;
    AttFace.captureVideoBlob(video).then(function (blob) {
      if (!blob) {
        if (liveStatus) liveStatus.textContent = 'گرفتن عکس ناموفق بود. دوباره تلاش کنید.';
        punchBtn.disabled = false;
        setTimeout(loopDetect, 1000);
        return;
      }
      var file = new File([blob], 'punch.jpg', { type: 'image/jpeg' });
      var dt = new DataTransfer();
      dt.items.add(file);
      photo.files = dt.files;
      face.value = '1';
      method.value = 'selfie';
      if (!lat.value || !lng.value) {
        if (liveStatus) liveStatus.textContent = 'GPS هنوز آماده نیست. صبر کنید و دوباره دکمه را بزنید.';
        punchBtn.disabled = false;
        return;
      }
      submitted = true;
      stopCam();
      form.submit();
    });
  }

  punchBtn.addEventListener('click', function () {
    if (!stream) {
      openBtn.click();
      return;
    }
    if (liveStatus) liveStatus.textContent = 'در حال تشخیص چهره و ثبت…';
    punchBtn.disabled = true;
    AttFace.detectFromVideo(video).then(function (res) {
      if (!res || !res.ok) {
        if (liveStatus) liveStatus.textContent = 'چهره پیدا نشد. صورت را روبه‌رو بگیرید.';
        punchBtn.disabled = false;
        return;
      }
      doAutoPunch();
    }).catch(function (e) {
      if (liveStatus) liveStatus.textContent = 'خطا: ' + (e && e.message ? e.message : '');
      punchBtn.disabled = false;
    });
  });

  window.addEventListener('beforeunload', stopCam);
})();
</script>
@endsection
