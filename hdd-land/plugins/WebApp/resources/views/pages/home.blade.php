@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $quickLinks = $quickLinks ?? [];
@endphp

@if(!empty($s['hero_enabled']))
@php
  $mh = is_array($mobileHero ?? null) ? $mobileHero : [];
  $heroImage = trim((string)($mh['image'] ?? ''));
@endphp
<section class="wa-hero {{ $heroImage ? 'wa-hero-image' : '' }}" @if($heroImage) style="background-image:url('{{ $heroImage }}')" @endif>
  @if($heroImage)<div class="wa-hero-overlay"></div>@endif
  <div class="wa-hero-glow"></div>
  <h1>{{ $mh['title'] ?? $s['hero_title'] ?? $s['app_name'] ?? 'سرزمین هارد' }}</h1>
  <p>{{ $mh['text'] ?? $s['hero_text'] ?? '' }}</p>
  <a class="wa-cta" href="{{ url($mh['cta_url'] ?? $s['hero_cta_url'] ?? '/app/shop') }}">{{ $mh['cta_label'] ?? $s['hero_cta_label'] ?? 'مشاهده محصولات' }}</a>
</section>
@endif

@if(!empty($s['show_search']))
<form class="wa-search" action="{{ url('/app/shop') }}" method="get">
  <input type="search" name="q" placeholder="جستجوی محصول، برند، کد…" autocomplete="off">
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
<div class="wa-cats">
  @foreach($categories as $cat)
    <a class="wa-cat" href="{{ url('/app/shop?cat='.urlencode($cat->slug ?? '')) }}">{{ $cat->name }}</a>
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
@endsection
