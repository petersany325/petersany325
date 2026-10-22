@php
  \Plugins\Studio\Plugin::registerViews();
  $menu = $menu ?? \App\Support\SiteHost::studioMenu();
  $path = '/'.ltrim(request()->path(), '/');
  if ($path === '/index.php' || $path === '.') { $path = '/'; }
@endphp
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
      @foreach($menu as $item)
        @php $on = $path === $item['url'] || str_starts_with($path, rtrim($item['url'], '/').'/'); @endphp
        <a href="{{ url($item['url']) }}" class="{{ $on ? 'is-on' : '' }}">{{ $item['label'] }}</a>
      @endforeach
      <a href="{{ url('/contact') }}">تماس</a>
    </nav>
    <a class="st-cta st-cta--on" href="{{ url('/contact') }}">درخواست مشاوره</a>
  </div>
</header>
