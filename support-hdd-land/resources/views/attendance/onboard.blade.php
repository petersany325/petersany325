@extends('layouts.app')
@section('title', 'ثبت اولیه حضور | '.shop_name())
@section('page_title', 'ثبت اولیه حضور')
@section('window_title', 'سلفی + GPS موبایل — باز شدن منوی کار')

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
            <h2>ثبت اولیه ورود به سیستم حضور</h2>
            <p class="lead">برای بار اول بعد از ورود: یک سلفی واضح و موقعیت GPS همین موبایل را ثبت کنید. بعد از ذخیره، منوی کار آزاد می‌شود.</p>
        </div>
    </div>

    @if(! $officeReady)
        <div class="alert alert-error">مختصات شرکت هنوز توسط مدیر در تنظیمات حضور ثبت نشده. می‌توانید ثبت اولیه خود را انجام دهید؛ برای ورود/خروج داخل شعاع، مدیر باید GPS شرکت را تنظیم کند.</div>
    @endif

    <form method="POST" action="{{ route('attendance.onboard.store') }}" enctype="multipart/form-data" class="panel" style="padding:14px;" id="att-onboard-form">
        @csrf
        <input type="hidden" name="latitude" id="att-lat" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="att-lng" value="{{ old('longitude') }}">
        <input type="hidden" name="accuracy_m" id="att-acc" value="{{ old('accuracy_m') }}">
        <input type="hidden" name="device_fingerprint" id="att-device" value="">

        <h3 style="margin:0 0 8px;">۱) سلفی چهره</h3>
        <p class="muted" style="margin:0 0 10px;">دوربین جلو — نه از گالری. صورت واضح و رو به نور باشد.</p>
        <input type="file" name="photo" accept="image/*" capture="user" required>

        <h3 style="margin:18px 0 8px;">۲) GPS موبایل شما</h3>
        <p class="muted" style="margin:0 0 8px;">دسترسی مکان را در گوشی روشن کنید. دکمه زیر موقعیت را می‌خواند.</p>
        <button type="button" class="btn btn-secondary" id="att-read-gps">خواندن GPS موبایل</button>
        <div class="muted" id="att-gps-status" style="margin-top:8px;">هنوز موقعیت خوانده نشده.</div>

        <div style="margin-top:18px;">
            <button class="btn btn-primary" type="submit" id="att-submit" disabled>ثبت و باز کردن منوی کار</button>
        </div>
    </form>
</div>

<script>
(function () {
  var lat = document.getElementById('att-lat');
  var lng = document.getElementById('att-lng');
  var acc = document.getElementById('att-acc');
  var status = document.getElementById('att-gps-status');
  var submit = document.getElementById('att-submit');
  var device = document.getElementById('att-device');
  var photo = document.querySelector('input[name=photo]');

  try {
    var key = 'att_device_fp';
    var fp = localStorage.getItem(key);
    if (!fp) {
      fp = 'd_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36);
      localStorage.setItem(key, fp);
    }
    if (device) device.value = fp;
  } catch (e) {}

  function ready() {
    var ok = lat.value && lng.value && photo && photo.files && photo.files.length;
    if (submit) submit.disabled = !ok;
  }
  if (photo) photo.addEventListener('change', ready);

  function readGps() {
    if (!navigator.geolocation) {
      status.textContent = 'این مرورگر GPS را پشتیبانی نمی‌کند.';
      return;
    }
    status.textContent = 'در حال خواندن موقعیت… اجازه دسترسی را تأیید کنید.';
    navigator.geolocation.getCurrentPosition(function (pos) {
      lat.value = pos.coords.latitude.toFixed(7);
      lng.value = pos.coords.longitude.toFixed(7);
      acc.value = pos.coords.accuracy != null ? Math.round(pos.coords.accuracy) : '';
      status.textContent = 'GPS آماده است — دقت حدود ' + Math.round(pos.coords.accuracy || 0) + ' متر';
      ready();
    }, function (err) {
      status.textContent = 'خطا: ' + (err && err.message ? err.message : 'دسترسی مکان را در تنظیمات گوشی فعال کنید');
    }, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
  }

  document.getElementById('att-read-gps').addEventListener('click', readGps);
  // auto-try once
  readGps();
})();
</script>
@endsection
