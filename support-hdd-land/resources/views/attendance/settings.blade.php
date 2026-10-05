@extends('layouts.app')
@section('title', 'تنظیمات حضور و غیاب | '.shop_name())
@section('page_title', 'تنظیمات حضور و غیاب')
@section('window_title', 'GPS، شعاع شرکت، شیفت و روش‌های تأیید')

@section('content')
@php
  $lat = old('office_lat', $settings['office_lat']);
  $lng = old('office_lng', $settings['office_lng']);
  $label = old('office_label', $settings['office_label'] ?? '');
  $hasPoint = $lat !== null && $lat !== '' && $lng !== null && $lng !== '';
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
  .att-map-box{grid-column:1/-1;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;background:#fff}
  .att-map-toolbar{display:flex;flex-wrap:wrap;gap:8px;padding:10px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;align-items:center}
  .att-map-toolbar input[type=search]{flex:1;min-width:180px}
  .att-map-results{list-style:none;margin:0;padding:0;max-height:180px;overflow:auto;border-bottom:1px solid #e2e8f0}
  .att-map-results li{padding:10px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px;line-height:1.5}
  .att-map-results li:hover,.att-map-results li.is-active{background:#eff6ff}
  .att-map-results .muted{font-size:11px}
  #att-office-map{height:320px;width:100%;direction:ltr}
  .att-loc-card{grid-column:1/-1;padding:10px 12px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;margin-bottom:4px}
  .att-loc-card strong{display:block;margin-bottom:4px}
</style>

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
            <p class="lead">جستجوی آنلاین محل شرکت، انتخاب دقیق روی نقشه، و ویرایش دوباره در صورت جابه‌جایی</p>
        </div>
        <div>
            <a class="btn btn-ghost" href="{{ route('attendance.manage') }}">بازگشت به مدیریت</a>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance.settings.save') }}" class="panel form-grid" style="padding:14px;" id="att-settings-form">
        @csrf

        <div style="grid-column:1/-1;">
            <label><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))> فعال بودن سیستم حضور و غیاب</label>
        </div>

        <div style="grid-column:1/-1;"><h3 style="margin:8px 0;">موقعیت شرکت (GPS)</h3></div>

        <div class="att-loc-card" id="att-loc-summary">
            @if($hasPoint)
                <strong>موقعیت فعلی ذخیره‌شده</strong>
                <div id="att-loc-label-view">{{ $label !== '' ? $label : 'بدون نام — مختصات دستی' }}</div>
                <div class="muted" dir="ltr" id="att-loc-coords-view">{{ $lat }} , {{ $lng }}</div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-secondary" id="att-edit-loc">ویرایش / جابه‌جایی محل</button>
                    <a class="btn btn-ghost" id="att-open-maps" href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" target="_blank" rel="noopener">باز کردن در نقشه</a>
                </div>
            @else
                <strong>هنوز موقعیت شرکت تنظیم نشده</strong>
                <div class="muted">با جستجوی آنلاین آدرس را پیدا کنید یا روی نقشه نقطه بگذارید، بعد ذخیره کنید.</div>
            @endif
        </div>

        <div class="att-map-box" id="att-map-panel" @if($hasPoint) style="display:none" @endif>
            <div class="att-map-toolbar">
                <input type="search" id="att-geo-q" placeholder="جستجوی آنلاین آدرس… مثلاً آمل بلوار طبری" dir="rtl" autocomplete="off">
                <button type="button" class="btn btn-primary" id="att-geo-search">جستجوی آنلاین</button>
                <button type="button" class="btn btn-secondary" id="att-fill-here">موقعیت همین دستگاه</button>
                <button type="button" class="btn btn-ghost" id="att-clear-loc">پاک کردن نقطه</button>
            </div>
            <div class="muted" style="padding:6px 12px;font-size:12px;" id="att-geo-status">آدرس را بنویسید و جستجو کنید، یا روی نقشه کلیک/درگ کنید تا محل دقیق انتخاب شود.</div>
            <ul class="att-map-results" id="att-geo-results" hidden></ul>
            <div id="att-office-map"></div>
        </div>

        <input type="hidden" name="office_label" id="att-office-label" value="{{ $label }}">
        <div>
            <label>عرض جغرافیایی (Latitude)</label>
            <input type="text" name="office_lat" id="att-office-lat" value="{{ $lat }}" dir="ltr" placeholder="36.4617770">
        </div>
        <div>
            <label>طول جغرافیایی (Longitude)</label>
            <input type="text" name="office_lng" id="att-office-lng" value="{{ $lng }}" dir="ltr" placeholder="52.3495880">
        </div>
        <div>
            <label>شعاع مجاز (متر)</label>
            <input type="number" name="geofence_radius_m" id="att-radius" min="20" max="5000" value="{{ old('geofence_radius_m', $settings['geofence_radius_m']) }}" dir="ltr">
            <div class="muted" style="font-size:11px;">دایره روی نقشه = شعاع مجاز حضور</div>
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
            <span class="muted" style="margin-right:8px;">بعد از انتخاب محل جدید حتماً ذخیره را بزنید.</span>
        </div>
    </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
  var GEO_URL = @json(route('attendance.geocode'));
  var latEl = document.getElementById('att-office-lat');
  var lngEl = document.getElementById('att-office-lng');
  var labelEl = document.getElementById('att-office-label');
  var radiusEl = document.getElementById('att-radius');
  var statusEl = document.getElementById('att-geo-status');
  var resultsEl = document.getElementById('att-geo-results');
  var qEl = document.getElementById('att-geo-q');
  var panel = document.getElementById('att-map-panel');
  var map, marker, circle, mapReady = false;

  function num(v, fallback) {
    var n = parseFloat(v);
    return isFinite(n) ? n : fallback;
  }

  function radiusM() {
    return Math.max(20, Math.min(5000, parseInt(radiusEl && radiusEl.value ? radiusEl.value : '100', 10) || 100));
  }

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg || '';
  }

  function syncSummary() {
    var lat = latEl.value, lng = lngEl.value, label = labelEl.value;
    var viewL = document.getElementById('att-loc-label-view');
    var viewC = document.getElementById('att-loc-coords-view');
    var maps = document.getElementById('att-open-maps');
    if (viewL) viewL.textContent = label || 'بدون نام — مختصات دستی';
    if (viewC) viewC.textContent = (lat && lng) ? (lat + ' , ' + lng) : '—';
    if (maps && lat && lng) maps.href = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
  }

  function setPoint(lat, lng, label, fromUser) {
    lat = Number(lat).toFixed(7);
    lng = Number(lng).toFixed(7);
    latEl.value = lat;
    lngEl.value = lng;
    if (typeof label === 'string') labelEl.value = label;
    syncSummary();
    if (!mapReady) return;
    var ll = L.latLng(parseFloat(lat), parseFloat(lng));
    if (!marker) {
      marker = L.marker(ll, { draggable: true }).addTo(map);
      marker.on('dragend', function () {
        var p = marker.getLatLng();
        setPoint(p.lat, p.lng, labelEl.value || 'انتخاب روی نقشه', true);
      });
    } else {
      marker.setLatLng(ll);
    }
    if (!circle) {
      circle = L.circle(ll, { radius: radiusM(), color: '#2563eb', fillColor: '#3b82f6', fillOpacity: 0.15 }).addTo(map);
    } else {
      circle.setLatLng(ll);
      circle.setRadius(radiusM());
    }
    if (fromUser) map.setView(ll, Math.max(map.getZoom(), 17));
    else map.setView(ll, 17);
  }

  function clearPoint() {
    latEl.value = '';
    lngEl.value = '';
    labelEl.value = '';
    syncSummary();
    if (marker) { map.removeLayer(marker); marker = null; }
    if (circle) { map.removeLayer(circle); circle = null; }
    setStatus('نقطه پاک شد. محل جدید را جستجو یا روی نقشه انتخاب کنید.');
  }

  function ensureMap() {
    if (mapReady) {
      setTimeout(function () { map.invalidateSize(); }, 50);
      return;
    }
    var startLat = num(latEl.value, 35.6892);
    var startLng = num(lngEl.value, 51.3890);
    var zoom = (latEl.value && lngEl.value) ? 17 : 6;
    map = L.map('att-office-map').setView([startLat, startLng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    map.on('click', function (e) {
      setPoint(e.latlng.lat, e.latlng.lng, 'انتخاب دستی روی نقشه', true);
      setStatus('نقطه روی نقشه انتخاب شد. در صورت جابه‌جایی، مارکر را بکشید و ذخیره کنید.');
    });
    mapReady = true;
    if (latEl.value && lngEl.value) setPoint(latEl.value, lngEl.value, labelEl.value, false);
    setTimeout(function () { map.invalidateSize(); }, 100);
  }

  function showPanel(show) {
    if (!panel) return;
    panel.style.display = show ? '' : 'none';
    if (show) ensureMap();
  }

  var editBtn = document.getElementById('att-edit-loc');
  if (editBtn) editBtn.addEventListener('click', function () {
    showPanel(true);
    setStatus('می‌توانید دوباره جستجو کنید یا مارکر را جابه‌جا کنید، بعد ذخیره بزنید.');
  });

  document.getElementById('att-clear-loc').addEventListener('click', clearPoint);

  document.getElementById('att-fill-here').addEventListener('click', function () {
    if (!navigator.geolocation) { setStatus('GPS پشتیبانی نمی‌شود'); return; }
    setStatus('در حال خواندن موقعیت دستگاه…');
    ensureMap();
    navigator.geolocation.getCurrentPosition(function (pos) {
      setPoint(pos.coords.latitude, pos.coords.longitude, 'موقعیت فعلی این دستگاه', true);
      setStatus('از GPS دستگاه تنظیم شد (±' + Math.round(pos.coords.accuracy || 0) + ' متر). ذخیره را بزنید.');
    }, function (err) {
      setStatus(err && err.message ? err.message : 'خطا در GPS');
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
  });

  function renderResults(rows) {
    resultsEl.innerHTML = '';
    if (!rows || !rows.length) {
      resultsEl.hidden = true;
      setStatus('نتیجه‌ای پیدا نشد. عبارت دقیق‌تری مثل «شهر + خیابان» بنویسید.');
      return;
    }
    resultsEl.hidden = false;
    rows.forEach(function (row, idx) {
      var li = document.createElement('li');
      li.innerHTML = '<div>' + (row.label || '') + '</div><div class="muted" dir="ltr">' + row.lat + ', ' + row.lng + '</div>';
      li.addEventListener('click', function () {
        Array.prototype.forEach.call(resultsEl.querySelectorAll('li'), function (n) { n.classList.remove('is-active'); });
        li.classList.add('is-active');
        setPoint(row.lat, row.lng, row.label || '', true);
        setStatus('محل از نتایج جستجو انتخاب شد. برای دقت بیشتر مارکر را جابه‌جا کنید، بعد ذخیره کنید.');
      });
      if (idx === 0) li.classList.add('is-active');
      resultsEl.appendChild(li);
    });
  }

  function doSearch() {
    var q = (qEl.value || '').trim();
    if (q.length < 3) { setStatus('حداقل ۳ حرف بنویسید.'); return; }
    setStatus('در حال جستجوی آنلاین…');
    resultsEl.hidden = true;
    ensureMap();
    fetch(GEO_URL + '?q=' + encodeURIComponent(q), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (pack) {
        if (!pack.ok || !pack.j.ok) {
          setStatus((pack.j && pack.j.message) || 'جستجو ناموفق بود.');
          return;
        }
        renderResults(pack.j.results || []);
        var first = (pack.j.results || [])[0];
        if (first) setPoint(first.lat, first.lng, first.label || '', true);
      })
      .catch(function () { setStatus('خطا در ارتباط با جستجوی نقشه.'); });
  }

  document.getElementById('att-geo-search').addEventListener('click', doSearch);
  qEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
  });

  if (radiusEl) radiusEl.addEventListener('change', function () {
    if (circle) circle.setRadius(radiusM());
  });

  // اگر هنوز نقطه‌ای نیست، پنل نقشه از اول باز باشد
  if (panel && panel.style.display !== 'none') ensureMap();
})();
</script>
@endsection
