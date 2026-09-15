@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $quickLinks = $quickLinks ?? [];
  $home = \App\Support\HomePageConfig::get();
  $trust = \App\Support\HomePageConfig::trustItems($home);
  $mh = is_array($mobileHero ?? null) ? $mobileHero : [];
@endphp

@if(!empty($s['hero_enabled']) || !empty($home['hero_enabled']))
@php
  $heroImage = trim((string) ($mh['image'] ?? ''));
  if ($heroImage === '' && !empty($home['hero_image'])) {
    $heroImage = \App\Support\HomePageConfig::imageUrl((string) $home['hero_image']);
  }
@endphp
<section
  class="wa-hero {{ $heroImage ? 'wa-hero-image' : '' }}"
  style="{{ \App\Support\HomePageConfig::heroStyleAttr($home) }}@if($heroImage);background-image:url('{{ $heroImage }}')@endif"
>
  @if($heroImage)<div class="wa-hero-overlay"></div>@endif
  <div class="wa-hero-glow"></div>
  <p class="wa-hero-kicker">{{ $mh['kicker'] ?? $home['hero_kicker'] ?? 'شرکت تخصصی ذخیره‌سازی' }}</p>
  <h1>{{ $mh['title'] ?? $s['hero_title'] ?? $home['hero_title'] ?? $s['app_name'] ?? 'سرزمین هارد' }}</h1>
  <p>{{ $mh['text'] ?? $s['hero_text'] ?? $home['hero_text'] ?? '' }}</p>
  <div class="wa-hero-actions">
    <a class="wa-cta" href="{{ url($mh['cta_url'] ?? $s['hero_cta_url'] ?? $home['hero_webapp_cta1_url'] ?? '/app/shop') }}">{{ $mh['cta_label'] ?? $s['hero_cta_label'] ?? $home['hero_cta1_label'] ?? 'ورود به فروشگاه' }}</a>
    <a class="wa-cta wa-cta-ghost" href="{{ url($mh['cta2_url'] ?? $home['hero_cta2_url'] ?? '/contact') }}">{{ $mh['cta2_label'] ?? $home['hero_cta2_label'] ?? 'درخواست سازمانی' }}</a>
  </div>
  @if(!empty($home['hero_merge_enabled']) && !empty($home['hero_merge_image']))
    <img class="wa-hero-merge" src="{{ \App\Support\HomePageConfig::imageUrl((string) $home['hero_merge_image']) }}" alt="" loading="lazy">
  @endif
</section>
@endif

@if(!empty($home['trust_enabled']) && $trust !== [])
<section class="wa-trust" aria-label="اعتماد">
  @foreach($trust as $item)
    <div class="wa-trust-item"><strong>{{ $item['title'] }}</strong><span>{{ $item['text'] }}</span></div>
  @endforeach
</section>
@endif

@if(!empty($s['show_search']))
<form class="wa-search" action="{{ url('/app/shop') }}" method="get">
  <input type="search" name="q" placeholder="{{ $home['search_placeholder'] ?? 'جستجوی محصول، برند، کد…' }}" autocomplete="off">
  <button type="submit">برو</button>
</form>
@endif

@if(!empty($s['show_quick_links']) && !empty($quickLinks))
<div class="wa-quick">
  @foreach($quickLinks as $ql)
    <a href="{{ str_starts_with($ql['url'], 'http') ? $ql['url'] : url($ql['url']) }}">{{ $ql['label'] }}</a>
  @endforeach
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

@if(!empty($s['show_featured']))
<div class="wa-section-head">
  <strong>{{ $s['featured_title'] ?? 'محصولات ویژه' }}</strong>
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

@if(!empty($home['corp_enabled']))
<section class="wa-corp">
  <img src="{{ \App\Support\HomePageConfig::imageUrl((string) ($home['webapp_corp_image'] ?? $home['corp_1_image'] ?? '')) }}" alt="" width="720" height="480" loading="lazy">
  <div>
    <strong>{{ $home['webapp_corp_title'] ?? $home['corp_title'] }}</strong>
    <p>{{ $home['webapp_corp_text'] ?? $home['corp_subtitle'] }}</p>
    <a class="wa-cta" href="{{ url($home['webapp_corp_cta_url'] ?? $home['corp_cta_url'] ?? '/contact') }}">{{ $home['webapp_corp_cta_label'] ?? $home['corp_cta_label'] ?? 'درخواست سازمانی' }}</a>
  </div>
</section>
@endif
@endsection
