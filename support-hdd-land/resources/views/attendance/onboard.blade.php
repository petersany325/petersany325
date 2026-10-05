@extends('layouts.app')
@section('title', 'ثبت اولیه حضور | '.shop_name())
@section('page_title', 'ثبت اولیه حضور')
@section('window_title', 'سلفی با تشخیص چهره + GPS — ارسال برای تأیید ادمین')

@section('content')
<div class="emp-cartable" style="max-width:640px;margin:0 auto;">
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>{{ !empty($isResubmit) ? 'ثبت مجدد سلفی' : 'ثبت اولیه ورود به سیستم حضور' }}</h2>
            <p class="lead">سلفی باید چهره شما را تشخیص دهد، بعد برای تأیید ادمین ارسال می‌شود. GPS موبایل هم یک‌بار ثبت می‌شود.</p>
            @if(!empty($isResubmit))
                <div class="alert alert-error" style="margin-top:8px;">سلفی قبلی رد شده بود — لطفاً سلفی جدید با چهره واضح بگیرید.</div>
            @endif
        </div>
    </div>

    @if(! $officeReady)
        <div class="alert alert-error">مختصات شرکت هنوز توسط مدیر تنظیم نشده. ثبت اولیه شما انجام می‌شود؛ ورود داخل شعاع بعد از تنظیم GPS شرکت ممکن است.</div>
    @endif

    <form method="POST" action="{{ route('attendance.onboard.store') }}" enctype="multipart/form-data" class="panel" style="padding:14px;" id="att-onboard-form">
        @csrf
        <input type="hidden" name="latitude" id="att-lat" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="att-lng" value="{{ old('longitude') }}">
        <input type="hidden" name="accuracy_m" id="att-acc" value="{{ old('accuracy_m') }}">
        <input type="hidden" name="device_fingerprint" id="att-device" value="">
        <input type="hidden" name="face_detected" id="att-face" value="0">

        <h3 style="margin:0 0 8px;">۱) سلفی چهره (تشخیص خودکار)</h3>
        <p class="muted" style="margin:0 0 10px;">دوربین جلو — صورت کامل در کادر باشد. بدون تشخیص چهره ارسال نمی‌شود.</p>
        <div style="margin-bottom:10px;">
            <video id="att-cam" playsinline autoplay muted style="width:100%;max-width:360px;border-radius:10px;background:#111;transform:scaleX(-1);"></video>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <button type="button" class="btn btn-secondary" id="att-start-cam">روشن کردن دوربین</button>
            <button type="button" class="btn btn-primary" id="att-snap" disabled>گرفتن سلفی + تشخیص چهره</button>
        </div>
        <input type="file" name="photo" id="att-photo" accept="image/*" capture="user" style="display:none;">
        <div class="muted" id="att-face-status">دوربین را روشن کنید و سلفی بگیرید.</div>
        <img id="att-preview" alt="" style="display:none;max-width:220px;margin-top:10px;border-radius:8px;border:1px solid #ddd;">

        <h3 style="margin:18px 0 8px;">۲) GPS موبایل شما</h3>
        <button type="button" class="btn btn-secondary" id="att-read-gps">خواندن GPS موبایل</button>
        <div class="muted" id="att-gps-status" style="margin-top:8px;">هنوز موقعیت خوانده نشده.</div>

        <div style="margin-top:18px;">
            <button class="btn btn-primary" type="submit" id="att-submit" disabled>ارسال برای تأیید ادمین</button>
        </div>
    </form>
</div>

@include('partials.attendance-face')
<script>
(function () {
  var lat = document.getElementById('att-lat');
  var lng = document.getElementById('att-lng');
  var acc = document.getElementById('att-acc');
  var face = document.getElementById('att-face');
  var statusGps = document.getElementById('att-gps-status');
  var statusFace = document.getElementById('att-face-status');
  var submit = document.getElementById('att-submit');
  var device = document.getElementById('att-device');
  var photo = document.getElementById('att-photo');
  var video = document.getElementById('att-cam');
  var preview = document.getElementById('att-preview');
  var stream = null;

  try {
    var key = 'att_device_fp';
    var fp = localStorage.getItem(key);
    if (!fp) { fp = 'd_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36); localStorage.setItem(key, fp); }
    if (device) device.value = fp;
  } catch (e) {}

  function ready() {
    submit.disabled = !(lat.value && lng.value && face.value === '1' && photo.files && photo.files.length);
  }

  document.getElementById('att-start-cam').addEventListener('click', function () {
    statusFace.textContent = 'درخواست دسترسی دوربین…';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
      .then(function (s) {
        stream = s;
        video.srcObject = s;
        document.getElementById('att-snap').disabled = false;
        statusFace.textContent = 'صورت را روبه‌روی دوربین بگیرید، بعد «گرفتن سلفی» را بزنید.';
        AttFace.load().catch(function () {});
      })
      .catch(function (err) {
        statusFace.textContent = 'دوربین در دسترس نیست: ' + (err && err.message ? err.message : '');
      });
  });

  document.getElementById('att-snap').addEventListener('click', function () {
    if (!stream) return;
    statusFace.textContent = 'در حال تشخیص چهره…';
    AttFace.detectFromVideo(video).then(function (res) {
      if (!res.ok) {
        face.value = '0';
        statusFace.textContent = 'چهره پیدا نشد. نور را بهتر کنید و دوباره بگیرید.';
        ready();
        return null;
      }
      return AttFace.captureVideoBlob(video).then(function (blob) {
        var file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
        var dt = new DataTransfer();
        dt.items.add(file);
        photo.files = dt.files;
        face.value = '1';
        preview.src = URL.createObjectURL(blob);
        preview.style.display = 'block';
        statusFace.textContent = 'چهره تشخیص داده شد ✓ — آماده ارسال برای تأیید ادمین';
        ready();
      });
    }).catch(function (e) {
      face.value = '0';
      statusFace.textContent = 'خطا در تشخیص چهره: ' + (e && e.message ? e.message : '');
      ready();
    });
  });

  function readGps() {
    if (!navigator.geolocation) { statusGps.textContent = 'GPS پشتیبانی نمی‌شود'; return; }
    statusGps.textContent = 'در حال خواندن موقعیت…';
    navigator.geolocation.getCurrentPosition(function (pos) {
      lat.value = pos.coords.latitude.toFixed(7);
      lng.value = pos.coords.longitude.toFixed(7);
      acc.value = pos.coords.accuracy != null ? Math.round(pos.coords.accuracy) : '';
      statusGps.textContent = 'GPS آماده (±' + Math.round(pos.coords.accuracy || 0) + ' متر)';
      ready();
    }, function (err) {
      statusGps.textContent = 'خطا: ' + (err && err.message ? err.message : 'دسترسی مکان را فعال کنید');
    }, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
  }
  document.getElementById('att-read-gps').addEventListener('click', readGps);
  readGps();
})();
</script>
@endsection
