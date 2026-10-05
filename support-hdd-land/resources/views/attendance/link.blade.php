<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $label }} | {{ shop_name() }}</title>
    <style>
        body{font-family:Tahoma,Arial,sans-serif;background:#f4f6f8;margin:0;padding:16px;color:#1f2937}
        .card{max-width:420px;margin:24px auto;background:#fff;border-radius:12px;padding:18px;box-shadow:0 8px 24px rgba(0,0,0,.08)}
        h1{font-size:18px;margin:0 0 8px}
        .muted{color:#6b7280;font-size:13px;line-height:1.7}
        .btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;font-size:14px;cursor:pointer}
        .btn-primary{background:#1d4ed8;color:#fff}
        .btn-primary:disabled{opacity:.5}
        .alert{padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:13px}
        .alert-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
        .alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        img.selfie{width:160px;height:160px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#111;display:block;margin:10px 0}
        label.confirm{display:flex;gap:8px;align-items:flex-start;font-size:13px;margin:12px 0}
    </style>
</head>
<body>
<div class="card">
    @if(session('success'))
        <div class="alert alert-ok">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">{{ $errors->first() }}</div>
    @endif

    <h1>{{ $label }} — {{ $employee->name }}</h1>
    <p class="muted">لینک یک‌بارمصرف پیامک. وضعیت هر روز از نیمه‌شب ریست می‌شود.</p>

    @if($profile?->isSelfieApproved())
        <p class="muted" style="margin-bottom:4px;">عکس تأییدشده شما:</p>
        <img class="selfie" src="{{ route('attendance.link.photo', $token) }}" alt="سلفی">
    @endif

    <form method="POST" action="{{ route('attendance.link.submit', $token) }}" id="att-link-form">
        @csrf
        <input type="hidden" name="latitude" id="att-lat">
        <input type="hidden" name="longitude" id="att-lng">
        <input type="hidden" name="accuracy_m" id="att-acc">
        <input type="hidden" name="device_fingerprint" id="att-device">
        @if($profile?->isSelfieApproved())
            <label class="confirm">
                <input type="checkbox" name="photo_confirmed" value="1" id="att-photo-ok" required>
                <span>این عکس من است و تأیید می‌کنم که خودم {{ $label }} را ثبت می‌کنم.</span>
            </label>
        @endif
        <div class="muted" id="att-gps-status">در حال دریافت GPS…</div>
        <div style="margin-top:14px;">
            <button class="btn btn-primary" type="submit" id="att-submit" disabled>{{ $label }}</button>
        </div>
    </form>
</div>
<script>
(function(){
  var lat=document.getElementById('att-lat');
  var lng=document.getElementById('att-lng');
  var acc=document.getElementById('att-acc');
  var status=document.getElementById('att-gps-status');
  var submit=document.getElementById('att-submit');
  var device=document.getElementById('att-device');
  var needPhoto={{ $profile?->isSelfieApproved() ? 'true' : 'false' }};
  var photoOk=document.getElementById('att-photo-ok');
  try{
    var key='att_device_fp';
    var fp=localStorage.getItem(key);
    if(!fp){fp='d_'+Math.random().toString(36).slice(2)+'_'+Date.now().toString(36);localStorage.setItem(key,fp);}
    if(device) device.value=fp;
  }catch(e){}
  function ready(){
    var gps=!!(lat.value&&lng.value);
    var photo=!needPhoto || (photoOk&&photoOk.checked);
    submit.disabled=!(gps&&photo);
  }
  if(photoOk) photoOk.addEventListener('change', ready);
  if(!navigator.geolocation){ status.textContent='GPS پشتیبانی نمی‌شود'; return; }
  navigator.geolocation.getCurrentPosition(function(pos){
    lat.value=pos.coords.latitude.toFixed(7);
    lng.value=pos.coords.longitude.toFixed(7);
    acc.value=pos.coords.accuracy!=null?Math.round(pos.coords.accuracy):'';
    status.textContent='GPS آماده (±'+Math.round(pos.coords.accuracy||0)+' متر)';
    ready();
  }, function(err){
    status.textContent='خطا در GPS: '+(err&&err.message?err.message:'دسترسی مکان را فعال کنید');
  }, {enableHighAccuracy:true,timeout:25000,maximumAge:0});
})();
</script>
</body>
</html>
