@php
  $hlBar = $hlAdminBar ?? null;
  if ($hlBar === null) {
    try { $hlBar = \App\Support\AdminToolbar::current(); } catch (\Throwable $e) { $hlBar = null; }
  }
@endphp
@if($hlBar)
<style>
html.has-hl-adminbar{margin-top:36px}
.hl-adminbar{position:fixed;top:0;left:0;right:0;z-index:100000;height:36px;background:#1d2327;color:#f0f0f1;font-family:Vazirmatn,Tahoma,sans-serif;font-size:13px;font-weight:650}
.hl-adminbar__inner{display:flex;align-items:stretch;width:min(1200px,100%);margin-inline:auto;height:100%;padding:0 .35rem}
.hl-adminbar a,.hl-adminbar button{display:inline-flex;align-items:center;height:100%;padding:0 .7rem;color:#f0f0f1;background:0;border:0;text-decoration:none;font:inherit;cursor:pointer;white-space:nowrap}
.hl-adminbar a:hover,.hl-adminbar button:hover,.hl-adminbar__dd.is-open>button{background:#2c3338;color:#fff}
.hl-adminbar__edit{background:#c45c26!important;color:#fff!important;font-weight:800}
.hl-adminbar__grow{flex:1}
.hl-adminbar__dd{position:relative}
.hl-adminbar__menu{position:absolute;top:100%;right:0;min-width:220px;background:#2c3338;border:1px solid #3c434a;padding:.28rem 0;display:grid}
.hl-adminbar__menu--end{right:auto;left:0}
.hl-adminbar__menu[hidden]{display:none}
.hl-adminbar__menu a,.hl-adminbar__menu button{display:flex;width:100%;height:auto;padding:.55rem .85rem;text-align:right}
.hdr-util--edit{background:#c45c26!important;color:#fff!important;border-radius:999px;padding:.28rem .7rem!important;font-weight:800!important}
@media(max-width:820px){html.has-hl-adminbar{margin-top:44px}.hl-adminbar{height:44px}.hl-adminbar__where,.hl-adminbar__dd:not(.hl-adminbar__dd--user){display:none}}
</style>
<div class="hl-adminbar" id="hlAdminBar" dir="rtl">
  <div class="hl-adminbar__inner">
    <a class="hl-adminbar__brand" href="{{ $hlBar['home_url'] }}" title="مشاهده سایت">
      <img src="{{ asset('images/hdd-land-icon-32.png') }}?v=1" width="18" height="18" alt="">
      <span>HDD LAND</span>
    </a>

    <span class="hl-adminbar__where" title="صفحه جاری">{{ $hlBar['context_label'] }}</span>

    @if(!empty($hlBar['primary']))
      <a class="hl-adminbar__edit" href="{{ $hlBar['primary']['url'] }}">{{ $hlBar['primary']['label'] }}</a>
    @endif

    @if(!empty($hlBar['links']))
      <div class="hl-adminbar__dd" data-hl-dd>
        <button type="button" class="hl-adminbar__btn" aria-expanded="false">این صفحه</button>
        <div class="hl-adminbar__menu" hidden>
          @foreach($hlBar['links'] as $item)
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($hlBar['design']))
      <div class="hl-adminbar__dd" data-hl-dd>
        <button type="button" class="hl-adminbar__btn" aria-expanded="false">ظاهر سایت</button>
        <div class="hl-adminbar__menu" hidden>
          @foreach($hlBar['design'] as $item)
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
          @endforeach
        </div>
      </div>
    @endif

    @if(!empty($hlBar['new']))
      <div class="hl-adminbar__dd" data-hl-dd>
        <button type="button" class="hl-adminbar__btn" aria-expanded="false">جدید</button>
        <div class="hl-adminbar__menu" hidden>
          @foreach($hlBar['new'] as $item)
            <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
          @endforeach
        </div>
      </div>
    @endif

    <a class="hl-adminbar__panel" href="{{ $hlBar['panel_url'] }}">{{ $hlBar['panel_label'] }}</a>

    <div class="hl-adminbar__grow"></div>

    <div class="hl-adminbar__dd hl-adminbar__dd--user" data-hl-dd>
      <button type="button" class="hl-adminbar__btn" aria-expanded="false">سلام، {{ $hlBar['user_name'] }}</button>
      <div class="hl-adminbar__menu hl-adminbar__menu--end" hidden>
        <a href="{{ $hlBar['panel_url'] }}">{{ $hlBar['panel_label'] }}</a>
        @if($hlBar['is_admin'])
          <a href="{{ $hlBar['staff_url'] }}">پنل کارمند</a>
        @endif
        <a href="{{ $hlBar['account_url'] }}">حساب کاربری</a>
        <a href="{{ $hlBar['home_url'] }}">صفحه اول سایت</a>
        <form action="{{ url('/logout') }}" method="post">@csrf
          <button type="submit">خروج</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="{{ asset('js/admin-toolbar.js') }}?v=1" defer></script>
@endif
