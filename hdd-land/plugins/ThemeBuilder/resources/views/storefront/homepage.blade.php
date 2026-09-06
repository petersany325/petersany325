@php
  $themeClass = \Plugins\ThemeBuilder\src\ThemeConfig::class;
  $theme = class_exists($themeClass) ? $themeClass::get() : [];
  $banner = is_array($theme['banner'] ?? null) ? $theme['banner'] : [];
  $useRevolution = class_exists($themeClass) && $themeClass::bannerIsLive($banner);
  // top_menu (چیپ‌های قرمز زیر بنر) عمداً از پیش‌فرض حذف شده — تکراری با منوی هدر و دسته‌هاست.
  // banner/hero از حلقه خارج می‌شوند تا دوبار رندر نشوند؛ بنر Revolution جداگانه بالا می‌آید.
  $order = $theme['layout_order'] ?? ['banner','online','categories','featured','features','cta'];
  $order = array_values(array_filter($order, fn ($s) => !in_array($s, ['top_menu','banner','hero','features','online','cta'], true)));
  $featured = $featured ?? collect();
  $latest = $latest ?? collect();
  $categories = $categories ?? collect();
@endphp

@if($useRevolution)
  @include('theme-builder::storefront.partials.banner', ['b' => $banner])
@endif

{{-- home-hero: اگر Revolution زنده باشد فقط نوار اعتماد و بقیه را نگه می‌دارد --}}
@include('storefront.partials.home-hero', ['skipHero' => $useRevolution])

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
