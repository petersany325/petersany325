@php
  $studioHome = $studioHome ?? (class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::get() : []);
  $waOn = class_exists(\App\Support\StudioHomeConfig::class) && \App\Support\StudioHomeConfig::mobileWebAppOn($studioHome);
  $tabs = $waOn ? \App\Support\StudioHomeConfig::mobileTabs($studioHome) : [];
  $path = '/'.ltrim(request()->path(), '/');
  if ($path === '/index.php' || $path === '.') { $path = '/'; }
  $showBanner = $waOn && ! session('studio_webapp_banner_dismissed');
@endphp
@if($waOn)
  @if($showBanner)
    <div class="ag-install" id="agInstall" hidden dir="rtl" lang="fa">
      <div>
        <b>نصب وب‌اپ فراز نت آمل</b>
        <small>روی صفحه اصلی گوشی — طراحی سایت، حسابداری و تعمیرگاه</small>
      </div>
      <div class="ag-install__acts">
        <button type="button" id="agInstallBtn">نصب</button>
        <button type="button" id="agInstallDismiss" aria-label="بستن">×</button>
      </div>
    </div>
    <div class="ag-install ag-install--on" id="agInstalled" hidden>نصب‌شده روی این دستگاه</div>
  @endif
  <nav class="ag-tabbar" aria-label="منوی موبایل استودیو">
    @foreach($tabs as $tab)
      @php
        $u = $tab['url'];
        $on = $path === $u || ($u !== '/' && str_starts_with($path, rtrim($u, '/').'/'));
      @endphp
      <a class="{{ $on ? 'is-on' : '' }}" href="{{ url($u) }}"><span>{{ $tab['icon'] }}</span>{{ $tab['label'] }}</a>
    @endforeach
  </nav>
  <script>
    window.STUDIO_WA = {
      dismissUrl: @json(url('/studio-webapp/dismiss')),
      csrf: @json(csrf_token()),
      swUrl: @json(url('/studio-sw.js'))
    };
    (function () {
      var d = window.STUDIO_WA || {};
      var deferred = null;
      var banner = document.getElementById('agInstall');
      var installed = document.getElementById('agInstalled');
      var btn = document.getElementById('agInstallBtn');
      var hide = document.getElementById('agInstallDismiss');
      var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
      var ua = navigator.userAgent || '';
      var phone = /Android|iPhone|iPad|iPod/i.test(ua);
      if (phone && d.swUrl && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register(d.swUrl, { scope: '/' }).catch(function () {});
      }
      if (phone && standalone && installed) { installed.hidden = false; }
      else if (phone && banner) { banner.hidden = false; }
      window.addEventListener('beforeinstallprompt', function (e) {
        if (!phone) return;
        e.preventDefault();
        deferred = e;
        if (banner) banner.hidden = false;
      });
      if (btn) btn.addEventListener('click', function () {
        if (deferred) {
          deferred.prompt();
          deferred.userChoice.finally(function () { deferred = null; if (banner) banner.hidden = true; });
          return;
        }
        var ios = /iPhone|iPad|iPod/i.test(navigator.userAgent || '');
        alert(ios ? 'Safari ← اشتراک‌گذاری ← Add to Home Screen' : 'منوی مرورگر ← نصب برنامه / افزودن به صفحه اصلی');
      });
      if (hide) hide.addEventListener('click', function () {
        if (banner) banner.hidden = true;
        try {
          fetch(d.dismissUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': d.csrf, 'Accept': 'application/json' } });
        } catch (e) {}
      });
    })();
  </script>
@endif
