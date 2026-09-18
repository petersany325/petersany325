@php
  $hlBar = $hlAdminBar ?? null;
  if ($hlBar === null) {
    try { $hlBar = \App\Support\AdminToolbar::current(); } catch (\Throwable $e) { $hlBar = null; }
  }
@endphp
@if($hlBar)
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
