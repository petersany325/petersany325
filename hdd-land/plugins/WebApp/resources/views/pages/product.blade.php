@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $p = $product;
@endphp

<a class="wa-back" href="{{ url('/app/shop') }}">‹ بازگشت به فروشگاه</a>

<div class="wa-product-hero">
  @if(!empty($p->image))
    <img src="{{ \Plugins\WebApp\Plugin::productImageUrl($p->image ?? null) }}" alt="">
  @else
    <div class="wa-product-ph">{{ $p->part_type ?? 'PRODUCT' }}</div>
  @endif
</div>

<div class="wa-product-body">
  <h1>{{ $p->name }}</h1>
  @if(!empty($p->brand) || !empty($p->sku))
    <div class="wa-tags">
      @if(!empty($p->brand))<span>{{ $p->brand }}</span>@endif
      @if(!empty($p->sku))<span>SKU: {{ $p->sku }}</span>@endif
    </div>
  @endif
  <div class="wa-price-lg">{{ $fmt($p->price ?? 0) }} <small>تومان</small></div>
  @if(!empty($p->short_description))
    <p class="wa-desc">{{ $p->short_description }}</p>
  @elseif(!empty($p->description))
    <p class="wa-desc">{{ \Illuminate\Support\Str::limit(strip_tags($p->description), 280) }}</p>
  @endif

  @if(!empty($s['product_show_add_cart']) || !empty($s['product_show_buy_now']))
  <form method="post" action="{{ url('/app/cart/add') }}" class="wa-buy-box">
    @csrf
    <input type="hidden" name="product_id" value="{{ $p->id }}">
    <label class="wa-qty">
      تعداد
      <input type="number" name="qty" value="1" min="1" max="99">
    </label>
    <div class="wa-buy-actions">
      @if(!empty($s['product_show_add_cart']))
        <button class="wa-btn wa-btn-primary" type="submit">افزودن به سبد</button>
      @endif
      @if(!empty($s['product_show_buy_now']))
        <button class="wa-btn wa-btn-ghost" type="submit" name="buy_now" value="1">خرید سریع</button>
      @endif
    </div>
  </form>
  @endif
</div>

@if($related->isNotEmpty())
<div class="wa-section-head"><strong>محصولات مرتبط</strong></div>
<div class="wa-grid">
  @foreach($related as $r)
    <a class="wa-card" href="{{ url('/app/product/'.($r->slug ?? $r->id)) }}">
      <div class="wa-thumb">
        @if(!empty($r->image))
          <img src="{{ \Plugins\WebApp\Plugin::productImageUrl($r->image ?? null) }}" alt="" loading="lazy">
        @else<span>·</span>@endif
      </div>
      <div class="wa-meta">
        <strong>{{ $r->name }}</strong>
        <em>{{ $fmt($r->price ?? 0) }}</em>
      </div>
    </a>
  @endforeach
</div>
@endif
@endsection
