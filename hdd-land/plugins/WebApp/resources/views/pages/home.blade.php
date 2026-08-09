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
  <strong>{{ $s['featured_title'] ?? 'پرفروش‌ها' }}</strong>
  <a href="{{ url('/app/shop') }}">بیشتر</a>
</div>
<div class="wa-grid">
  @forelse($products as $p)
    <a class="wa-card" href="{{ url('/app/product/'.($p->slug ?? $p->id)) }}">
      <div class="wa-thumb">
        @if(!empty($p->image))
          <img src="{{ \Plugins\WebApp\Plugin::productImageUrl($p->image ?? null) }}" alt="" loading="lazy">
        @else
          <span>HDD</span>
        @endif
      </div>
      <div class="wa-meta">
        <strong>{{ $p->name }}</strong>
        <em>{{ $fmt($p->price ?? 0) }} <small>تومان</small></em>
      </div>
    </a>
  @empty
    <div class="wa-empty">{{ $s['empty_products_text'] ?? 'محصولی نیست' }}</div>
  @endforelse
</div>
@endif
@endsection
