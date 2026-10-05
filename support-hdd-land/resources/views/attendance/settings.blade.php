@extends('layouts.app')
@section('title', 'تنظیمات حضور و غیاب | '.shop_name())
@section('page_title', 'تنظیمات حضور و غیاب')
@section('window_title', 'GPS، شعاع شرکت، شیفت و روش‌های تأیید')

@section('content')
<div class="emp-cartable">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>تنظیمات حضور و غیاب</h2>
            <p class="lead">مختصات دقیق شرکت، شعاع مجاز، دقت GPS و قوانین ثبت</p>
        </div>
        <div>
            <a class="btn btn-ghost" href="{{ route('attendance.manage') }}">بازگشت به مدیریت</a>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance.settings.save') }}" class="panel form-grid" style="padding:14px;">
        @csrf

        <div style="grid-column:1/-1;">
            <label><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))> فعال بودن سیستم حضور و غیاب</label>
        </div>

        <div style="grid-column:1/-1;"><h3 style="margin:8px 0;">موقعیت شرکت (GPS)</h3></div>
        <div>
            <label>عرض جغرافیایی (Latitude)</label>
            <input type="text" name="office_lat" value="{{ old('office_lat', $settings['office_lat']) }}" dir="ltr" placeholder="36.2972">
        </div>
        <div>
            <label>طول جغرافیایی (Longitude)</label>
            <input type="text" name="office_lng" value="{{ old('office_lng', $settings['office_lng']) }}" dir="ltr" placeholder="59.6057">
        </div>
        <div>
            <label>شعاع مجاز (متر)</label>
            <input type="number" name="geofence_radius_m" min="20" max="5000" value="{{ old('geofence_radius_m', $settings['geofence_radius_m']) }}" dir="ltr">
            <div class="muted" style="font-size:11px;">پیشنهاد: ۸۰ تا ۱۵۰ متر برای ساختمان کوچک</div>
        </div>
        <div>
            <label>حداکثر خطای مجاز GPS (متر)</label>
            <input type="number" name="max_gps_accuracy_m" min="5" max="500" value="{{ old('max_gps_accuracy_m', $settings['max_gps_accuracy_m']) }}" dir="ltr">
            <div class="muted" style="font-size:11px;">اگر دقت گوشی بدتر از این باشد ثبت رد می‌شود</div>
        </div>
        <div style="grid-column:1/-1;">
            <label><input type="checkbox" name="require_gps" value="1" @checked(old('require_gps', $settings['require_gps']))> الزام GPS در هر ثبت</label>
            <label style="margin-right:16px;"><input type="checkbox" name="require_inside_geofence" value="1" @checked(old('require_inside_geofence', $settings['require_inside_geofence']))> فقط داخل شعاع شرکت قبول شود</label>
        </div>
        <div style="grid-column:1/-1;">
            <button class="btn btn-secondary" type="button" id="att-fill-here">پر کردن مختصات از همین دستگاه</button>
            <span class="muted" id="att-fill-status"></span>
        </div>

        <div style="grid-column:1/-1;"><h3 style="margin:8px 0;">روش‌های تأیید</h3></div>
        <div style="grid-column:1/-1;">
            <label><input type="checkbox" name="allow_selfie" value="1" @checked(old('allow_selfie', $settings['allow_selfie']))> اجازه سلفی</label>
            <label style="margin-right:16px;"><input type="checkbox" name="allow_otp" value="1" @checked(old('allow_otp', $settings['allow_otp']))> اجازه رمز یک‌بارمصرف SMS</label>
            <label style="margin-right:16px;"><input type="checkbox" name="require_enrolled_photo" value="1" @checked(old('require_enrolled_photo', $settings['require_enrolled_photo']))> برای سلفی، عکس مرجع ادمین الزامی باشد</label>
            <label style="margin-right:16px;"><input type="checkbox" name="bind_device" value="1" @checked(old('bind_device', $settings['bind_device']))> قفل دستگاه (اولین موبایل ثبت شود)</label>
        </div>

        <div style="grid-column:1/-1;"><h3 style="margin:8px 0;">شیفت و تأخیر</h3></div>
        <div>
            <label>شروع کار</label>
            <input type="time" name="work_start" value="{{ old('work_start', $settings['work_start']) }}" dir="ltr">
        </div>
        <div>
            <label>پایان کار</label>
            <input type="time" name="work_end" value="{{ old('work_end', $settings['work_end']) }}" dir="ltr">
        </div>
        <div>
            <label>تأخیر بعد از (دقیقه)</label>
            <input type="number" name="late_after_minutes" min="0" max="180" value="{{ old('late_after_minutes', $settings['late_after_minutes']) }}" dir="ltr">
        </div>
        <div>
            <label>حداقل فاصله دو ثبت (دقیقه)</label>
            <input type="number" name="min_minutes_between_punches" min="0" max="120" value="{{ old('min_minutes_between_punches', $settings['min_minutes_between_punches']) }}" dir="ltr">
        </div>

        <div style="grid-column:1/-1;">
            <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

<script>
(function () {
  const btn = document.getElementById('att-fill-here');
  const status = document.getElementById('att-fill-status');
  if (!btn) return;
  btn.addEventListener('click', function () {
    if (!navigator.geolocation) {
      status.textContent = 'GPS پشتیبانی نمی‌شود';
      return;
    }
    status.textContent = 'در حال خواندن…';
    navigator.geolocation.getCurrentPosition(function (pos) {
      document.querySelector('[name=office_lat]').value = pos.coords.latitude.toFixed(7);
      document.querySelector('[name=office_lng]').value = pos.coords.longitude.toFixed(7);
      status.textContent = 'تنظیم شد (±' + Math.round(pos.coords.accuracy || 0) + ' متر). ذخیره را بزنید.';
    }, function (err) {
      status.textContent = err.message || 'خطا';
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  });
})();
</script>
@endsection
