@php
  \Plugins\Studio\Plugin::registerViews();
  $studioHome = $studioHome ?? (class_exists(\App\Support\StudioHomeConfig::class) ? \App\Support\StudioHomeConfig::get() : []);
  $agency = $agency ?? ! empty($studioHome['enabled']);
  $path = '/'.ltrim(request()->path(), '/');
  if ($path === '/index.php' || $path === '.') { $path = '/'; }
  $items = $agency && class_exists(\App\Support\StudioHomeConfig::class)
    ? \App\Support\StudioHomeConfig::navItems($studioHome)
    : ($menu ?? \App\Support\SiteHost::studioMenu());
@endphp
@if($agency)
<header class="ag-head">
  <div class="ag-wrap ag-head__bar">
    <a class="ag-brand" href="{{ url('/') }}">
      <span class="ag-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11 12 3l8 8v9H4z"/><path d="M9 20v-7h6v7"/></svg>
      </span>
      <span>
        <b>{{ $studioHome['brand'] ?? 'سرزمین هارد' }}</b>
        <span>{{ $studioHome['tagline'] ?? 'تخصصی طراحی سایت و نرم‌افزار' }}</span>
      </span>
    </a>
    <nav class="ag-nav" aria-label="منوی استودیو">
      @foreach($items as $item)
        @php $url = $item['url'] ?? '#'; $on = $path === $url || ($url !== '/' && str_starts_with($path, rtrim($url, '/').'/')); @endphp
        <a href="{{ url($url) }}" class="{{ $on ? 'is-on' : '' }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>
    <div class="ag-tools">
      @if(!empty($studioHome['phone']))
        <a class="ag-phone" href="tel:{{ preg_replace('/\s+/', '', $studioHome['phone']) }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h3l1.5 4-2 1.5a12 12 0 0 0 6 6L17 13l4 1.5V18a2 2 0 0 1-2 2A16 16 0 0 1 5 6a2 2 0 0 1 2-2z"/></svg>
          {{ $studioHome['phone'] }}
        </a>
      @endif
      @if(!empty($studioHome['show_search']))
        <form action="{{ url('/contact') }}" method="get">
          <button class="ag-search" type="submit" aria-label="{{ $studioHome['search_placeholder'] ?? 'جستجو' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/></svg>
          </button>
        </form>
      @endif
    </div>
  </div>
</header>
@else
<header class="st-head">
  <div class="st-wrap st-head__bar">
    <a class="st-brand" href="{{ url('/') }}">
      <i class="st-mark" aria-hidden="true"></i>
      <span>
        <b>سرزمین هارد</b>
        <span>تخصصی طراحی سایت و نرم‌افزار</span>
      </span>
    </a>
    <nav class="st-nav" aria-label="منوی استودیو">
      @foreach($items as $item)
        @php $on = $path === $item['url'] || str_starts_with($path, rtrim($item['url'], '/').'/'); @endphp
        <a href="{{ url($item['url']) }}" class="{{ $on ? 'is-on' : '' }}">{{ $item['label'] }}</a>
      @endforeach
      <a href="{{ url('/contact') }}">تماس</a>
    </nav>
    <a class="st-cta st-cta--on" href="{{ url('/contact') }}">درخواست مشاوره</a>
  </div>
</header>
@endif
