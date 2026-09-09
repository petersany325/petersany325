@php
  // اگر home.blade بنر را بالاتر رندر کرده، اینجا دوباره تشخیص/رندر نکن.
  $alreadyFromHome = ! empty($revolutionAlreadyRendered) || ! empty($skipHero);

  $resolved = ['live' => false, 'banner' => []];
  $theme = [];
  if (! $alreadyFromHome && class_exists(\Plugins\ThemeBuilder\src\HomepageBanner::class)) {
    $resolved = \Plugins\ThemeBuilder\src\HomepageBanner::resolve();
  } elseif (! $alreadyFromHome) {
    $themeClass = \Plugins\ThemeBuilder\src\ThemeConfig::class;
    if (class_exists($themeClass) && method_exists($themeClass, 'get')) {
      try {
        $theme = $themeClass::get();
        $banner = is_array($theme['banner'] ?? null) ? $theme['banner'] : [];
        if (method_exists($themeClass, 'bannerIsLive')) {
          $resolved = ['live' => (bool) $themeClass::bannerIsLive($banner), 'banner' => $banner];
        }
      } catch (\Throwable) {
        $resolved = ['live' => false, 'banner' => []];
      }
    }
  }

  if (class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class) && method_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class, 'get')) {
    try {
      $theme = \Plugins\ThemeBuilder\src\ThemeConfig::get();
    } catch (\Throwable) {
      $theme = $theme ?? [];
    }
  }

  $useRevolution = $alreadyFromHome ? false : ! empty($resolved['live']);
  $banner = is_array($resolved['banner'] ?? null) ? $resolved['banner'] : [];

  // top_menu تکراری با هدر است؛ banner/hero جداگانه رندر می‌شوند.
  $order = $theme['layout_order'] ?? $theme['layout_order'] ?? ['banner','online','categories','featured','features','cta'];
  $order = array_values(array_filter($order, fn ($s) => !in_array($s, ['top_menu','banner','hero'], true)));
  $featured = $featured ?? collect();
  $latest = $latest ?? collect();
  $categories = $categories ?? collect();
@endphp

@if($useRevolution)
  @include('theme-builder::storefront.partials.banner', ['b' => $banner])
@endif

{{-- home-hero فقط وقتی بنرساز زنده نیست (یا بالاتر رندر نشده) --}}
@include('storefront.partials.home-hero', [
  'skipHero' => $useRevolution || $alreadyFromHome || ! empty($skipHero),
  'revolutionAlreadyRendered' => $useRevolution || $alreadyFromHome || ! empty($revolutionAlreadyRendered),
])

@foreach($order as $section)
  @if(is_string($section) && str_starts_with($section, 'block:'))
    @php $block = \Plugins\ThemeBuilder\src\ThemeConfig::findBlock($theme, substr($section, 6)); @endphp
    @if($block && !empty($block['enabled']))
      @include('theme-builder::storefront.partials.block', [
        'block' => $block,
        'featured' => $featured,
        'latest' => $latest,
        'categories' => $categories,
      ])
    @endif

  @elseif($section === 'online' && !empty($theme['online']['enabled']))
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'online','settings'=>$theme['online']]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])

  @elseif($section === 'hero' && !empty($theme['hero']['enabled']))
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'hero','settings'=>$theme['hero']]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])

  @elseif($section === 'categories' && !empty($theme['categories']['enabled']))
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'categories','settings'=>$theme['categories']]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])

  @elseif($section === 'featured' && !empty($theme['featured']['enabled']))
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'products','settings'=>array_merge($theme['featured'],['limit'=>max(1, min(24, (int)($theme['featured']['limit'] ?? 4))),'featured_only'=>'1'])]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])

  @elseif($section === 'features' && !empty($theme['features']['enabled']))
    @php
      $f = $theme['features'] ?? [];
      $items = is_array($f['items'] ?? null) ? $f['items'] : [];
      $fs = [
        'title' => $f['title'] ?? '',
        'item1_title' => $items[0]['title'] ?? '',
        'item1_text' => $items[0]['text'] ?? '',
        'item2_title' => $items[1]['title'] ?? '',
        'item2_text' => $items[1]['text'] ?? '',
        'item3_title' => $items[2]['title'] ?? '',
        'item3_text' => $items[2]['text'] ?? '',
      ];
    @endphp
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'features','settings'=>$fs]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])

  @elseif($section === 'cta' && !empty($theme['cta']['enabled']))
    @include('theme-builder::storefront.widgets', ['widgets'=>[['type'=>'cta','settings'=>$theme['cta']]], 'featured'=>$featured,'latest'=>$latest,'categories'=>$categories])
  @endif
@endforeach

@include('storefront.partials.home-corporate-sections')
