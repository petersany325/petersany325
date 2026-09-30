@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $home = \App\Support\HomePageConfig::get();
  $trust = \App\Support\HomePageConfig::trustItems($home);
  $mh = is_array($mobileHero ?? null) ? $mobileHero : [];
  $corpTiles = [];
  try {
    $corpTiles = \App\Support\HomePageConfig::corpTiles($home);
  } catch (\Throwable $e) {
    $corpTiles = [];
  }
  $mapUrl = static function (string $url): string {
    if (class_exists(\App\Support\PortalNav::class)) {
      return \App\Support\PortalNav::mapUrlForWebApp($url);
    }
    return $url;
  };
  $aboutParas = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($home['about_text'] ?? '')) ?: [])));
  $aboutLead = $aboutParas[0] ?? '';
@endphp

{{-- ۱) هیرو — همان ورودی سایت اصلی --}}
@if(!empty($s['hero_enabled']) || !empty($home['hero_enabled']))
@php
  $heroImage = trim((string) ($mh['image'] ?? ''));
  if ($heroImage === '' && !empty($home['hero_image'])) {
    $heroImage = \App\Support\HomePageConfig::imageUrl((string) $home['hero_image']);
  }
@endphp
<section
  class="wa-hero wa-hero--site {{ $heroImage ? 'wa-hero-image' : '' }}"
  style="{{ \App\Support\HomePageConfig::heroStyleAttr($home) }}@if($heroImage);--wa-hero-photo:url('{{ $heroImage }}')@endif"
  aria-label="هیرو فروشگاه"
>
  @if($heroImage)
    <span class="wa-hero-photo" aria-hidden="true"></span>
  @endif
  <div class="wa-hero-overlay"></div>
  <p class="wa-hero-brand">{{ $mh['brand'] ?? 'سرزمین هارد' }}</p>
  <h1>{!! $mh['title_html'] ?? e($mh['title'] ?? $home['hero_title'] ?? $s['hero_title'] ?? 'سرزمین هارد') !!}</h1>
  <p>{{ $mh['text'] ?? $home['hero_text'] ?? $s['hero_text'] ?? '' }}</p>
  <div class="wa-hero-actions">
    <a class="wa-cta" href="{{ url($mh['cta_url'] ?? $home['hero_webapp_cta1_url'] ?? '/app/shop') }}">{{ $mh['cta_label'] ?? $home['hero_cta1_label'] ?? 'ورود به فروشگاه' }}</a>
    <a class="wa-cta wa-cta-ghost" href="{{ url($mapUrl((string) ($mh['cta2_url'] ?? $home['hero_cta2_url'] ?? '/contact'))) }}">{{ $mh['cta2_label'] ?? $home['hero_cta2_label'] ?? 'درخواست سازمانی' }}</a>
  </div>
</section>
@endif

@if(!empty($s['show_search']))
<form class="wa-search" action="{{ url('/app/shop') }}" method="get">
  <input type="search" name="q" placeholder="{{ $home['search_placeholder'] ?? 'جستجوی محصول، برند، کد…' }}" autocomplete="off">
  <button type="submit">برو</button>
</form>
@endif

{{-- ۲) درباره — همان بند سایت اصلی --}}
@if(!empty($home['about_enabled']))
<section class="wa-band wa-band--about" aria-label="درباره فروشگاه">
  <span class="wa-band-photo" aria-hidden="true" style="background-image:url('{{ \App\Support\HomePageConfig::imageUrl((string) ($home['about_image'] ?? '')) }}')"></span>
  <h2>{{ $home['about_title'] ?? 'معرفی سرزمین هارد' }}</h2>
  @if($aboutLead !== '')
    <p>{{ $aboutLead }}</p>
  @endif
  <div class="wa-band-links">
    <a href="{{ url($mapUrl((string) ($home['about_cta1_url'] ?? '/about'))) }}">{{ $home['about_cta1_label'] ?? 'بیشتر بدانید' }}</a>
    <a href="{{ url($mapUrl((string) ($home['about_cta2_url'] ?? '/contact'))) }}">{{ $home['about_cta2_label'] ?? 'تماس با ما' }}</a>
  </div>
</section>
@endif

{{-- ۳–۴) سازمانی + چهار دایره مثل سایت اصلی --}}
@if(!empty($home['corp_enabled']))
<section class="wa-band wa-band--corp" aria-label="خرید سازمانی">
  <p class="wa-band-eye">{{ $home['corp_subtitle'] ?? '' }}</p>
  <h2>{{ $home['corp_cta_title'] ?: ($home['corp_title'] ?? '') }}</h2>
  <p>{{ $home['corp_cta_text'] ?? '' }}</p>
  <a class="wa-cta" href="{{ url($mapUrl((string) ($home['corp_cta_url'] ?? '/contact'))) }}">{{ $home['corp_cta_label'] ?? 'تماس با واحد فروش' }}</a>
</section>

@if($corpTiles !== [])
<section class="wa-tiles" aria-label="خدمات سازمانی">
  @auth
    @if((method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()) || (method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() && method_exists(auth()->user(), 'hasStaffPermission') && auth()->user()->hasStaffPermission('site.homepage')))
      <a class="wa-tiles-admin" href="{{ url('/admin/home-options') }}">ویرایش ۴ گزینه</a>
    @endif
  @endauth
  @foreach($corpTiles as $tile)
    <a class="wa-tile" href="{{ url($mapUrl((string) ($tile['url'] ?? '/contact'))) }}">
      <span class="wa-tile__media" aria-hidden="true">
        @if(($tile['image'] ?? '') !== '')
          <img src="{{ $tile['image'] }}" alt="" width="200" height="200" loading="lazy" decoding="async" onerror="this.remove()">
        @endif
      </span>
      <strong class="wa-tile__title">{{ $tile['title'] }}</strong>
      <span class="wa-tile__text">{{ $tile['text'] }}</span>
      <span class="wa-tile__link">{{ $home['tile_link_label'] ?? 'جزئیات' }}</span>
    </a>
  @endforeach
</section>
@endif
@endif

@if(!empty($s['show_featured']))
<div class="wa-section-head">
  <strong>{{ $home['featured_title'] ?? $s['featured_title'] ?? 'محصولات ویژه' }}</strong>
  <a href="{{ url('/app/shop') }}">مشاهده همه</a>
</div>
<div class="wa-featured" role="list">
  @forelse($products as $p)
    @php
      $price = (int) ($p->price ?? 0);
      $compare = (int) ($p->compare_price ?? 0);
      $status = (string) ($p->stock_status ?? 'instock');
      $stock = (int) ($p->stock ?? 0);
      $manage = !isset($p->manage_stock) || (bool) $p->manage_stock;
      $inStock = $status !== 'outofstock' && (!$manage || $stock > 0 || $status === 'onbackorder');
      $brand = trim((string) ($p->brand ?? ''));
      $capacity = trim((string) ($p->capacity ?? ''));
    @endphp
    <a class="wa-feat-card" role="listitem" href="{{ url('/app/product/'.($p->slug ?? $p->id)) }}">
      <div class="wa-feat-media">
        @if(!empty($p->image))
          <img src="{{ \Plugins\WebApp\Plugin::productImageUrl($p->image ?? null) }}" alt="" loading="lazy">
        @else
          <span>HDD</span>
        @endif
        <em class="wa-feat-chip">ویژه</em>
        @if(!$inStock)
          <i class="wa-feat-stock out">ناموجود</i>
        @elseif($stock > 0)
          <i class="wa-feat-stock ok">موجود</i>
        @endif
      </div>
      <div class="wa-feat-body">
        @if($brand || $capacity)
          <small class="wa-feat-meta">{{ trim($brand.($brand && $capacity ? ' · ' : '').$capacity) }}</small>
        @endif
        <strong>{{ $p->name }}</strong>
        <div class="wa-feat-price">
          @if($price > 0)
            <em>{{ $fmt($price) }} <small>تومان</small></em>
            @if($compare > $price)
              <s>{{ $fmt($compare) }}</s>
            @endif
          @else
            <em class="wa-feat-ask">تماس بگیرید</em>
          @endif
        </div>
      </div>
    </a>
  @empty
    <div class="wa-empty">{{ $s['empty_products_text'] ?? 'محصولی نیست' }}</div>
  @endforelse
</div>
@endif

@if(!empty($s['show_categories']) && $categories->isNotEmpty())
<div class="wa-section-head">
  <strong>دسته‌ها</strong>
  <a href="{{ url('/app/shop') }}">همه</a>
</div>
<div class="wa-cats wa-cats-photo">
  @foreach($categories as $cat)
    <a class="wa-cat-card" href="{{ url('/app/shop?cat='.urlencode($cat->slug ?? '')) }}">
      <img src="{{ \Plugins\WebApp\Plugin::categoryPhotoUrl($cat) }}" alt="" width="360" height="360" loading="lazy">
      <span>{{ $cat->name }}</span>
    </a>
  @endforeach
</div>
@endif

@if(!empty($home['edu_enabled']))
<section class="wa-edu" aria-label="آموزش">
  <div class="wa-section-head">
    <strong>{{ $home['edu_title'] }}</strong>
    <a href="{{ url($home['edu_more_url'] ?: '/blog') }}">همه</a>
  </div>
  <div class="wa-edu-row">
    @foreach([1,2,3] as $i)
      @php $title = trim((string) ($home['edu_'.$i.'_title'] ?? '')); @endphp
      @continue($title === '')
      <a class="wa-edu-card" href="{{ url($home['edu_'.$i.'_url'] ?: '/blog') }}">
        <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['edu_'.$i.'_image'] ?? '')) }}" alt="" width="480" height="320" loading="lazy">
        <strong>{{ $title }}</strong>
      </a>
    @endforeach
  </div>
</section>
@endif

@if(!empty($home['trust_enabled']) && $trust !== [])
<section class="wa-trust" aria-label="اعتماد">
  @foreach($trust as $item)
    <div class="wa-trust-item"><strong>{{ $item['title'] }}</strong><span>{{ $item['text'] }}</span></div>
  @endforeach
</section>
@endif
@endsection
