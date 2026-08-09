@extends('web-app::layout')
@section('content')
@php
  $fmt = fn ($n) => number_format((int) $n);
  $p = $product;
  $serials = $availableSerials ?? collect();
  $needsSerial = !empty($p->requires_serial);
  $hasW = !empty($p->has_warranty) || (!empty($p->warranty_type) && $p->warranty_type !== 'none') || !empty($p->warranty_months);
  $chips = array_filter([$p->capacity ?? null, $p->interface ?? null, $p->form_factor ?? null, $p->brand ?? null]);
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
  <div class="wa-pdp-brand">سرزمین هارد</div>
  <h1>{{ $p->name }}</h1>

  @if($chips)
    <div class="wa-tags">
      @foreach($chips as $chip)<span>{{ $chip }}</span>@endforeach
    </div>
  @elseif(!empty($p->brand) || !empty($p->sku))
    <div class="wa-tags">
      @if(!empty($p->brand))<span>{{ $p->brand }}</span>@endif
      @if(!empty($p->sku))<span>SKU: {{ $p->sku }}</span>@endif
    </div>
  @endif

  <div class="wa-pdp-badges">
    <span class="{{ $hasW ? 'ok' : '' }}">{{ $hasW ? 'گارانتی دارد' : 'فاقد گارانتی' }}</span>
    @if(!empty($p->stock_status))<span>{{ $p->stock_status === 'instock' ? 'موجود' : $p->stock_status }}</span>@endif
    @if($needsSerial)<span>نیاز به انتخاب سریال</span>@endif
  </div>

  <div class="wa-price-lg">{{ $fmt($p->price ?? 0) }} <small>تومان</small></div>
  @if(!empty($p->compare_price) && (int)$p->compare_price > (int)($p->price ?? 0))
    <div class="wa-old-price">{{ $fmt($p->compare_price) }} تومان</div>
  @endif

  @if(!empty($p->short_description))
    <p class="wa-desc">{{ $p->short_description }}</p>
  @elseif(!empty($p->description))
    <p class="wa-desc">{{ \Illuminate\Support\Str::limit(strip_tags($p->description), 280) }}</p>
  @endif

  @if($needsSerial && $serials->count() > 0)
    <form method="post" action="{{ url('/app/cart/add') }}" class="wa-buy-box wa-serial-box">
      @csrf
      <input type="hidden" name="product_id" value="{{ $p->id }}">
      <input type="hidden" name="qty" value="1">
      <label class="wa-serial-label">
        انتخاب سریال
        <select name="serial_id" required>
          <option value="">— انتخاب کنید —</option>
          @foreach($serials as $sn)
            <option value="{{ $sn->id }}">
              {{ $sn->serial }}
              @if(!empty($sn->warranty_company)) · {{ $sn->warranty_company }}@endif
            </option>
          @endforeach
        </select>
      </label>
      <p class="wa-serial-hint">سریال انتخابی پس از خرید در کارتابل «سریال‌ها و گارانتی من» نمایش داده می‌شود.</p>
      <div class="wa-buy-actions">
        @if(!empty($s['product_show_add_cart']))
          <button class="wa-btn wa-btn-primary" type="submit">افزودن با این سریال</button>
        @endif
        @if(!empty($s['product_show_buy_now']))
          <button class="wa-btn wa-btn-ghost" type="submit" name="buy_now" value="1">خرید سریع</button>
        @endif
      </div>
    </form>
  @elseif($needsSerial)
    <div class="wa-alert wa-err">سریال موجودی برای فروش ثبت نشده است.</div>
  @elseif(!empty($s['product_show_add_cart']) || !empty($s['product_show_buy_now']))
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

  <a class="wa-btn wa-btn-ghost wa-btn-block" href="{{ url('/serial-check') }}" style="margin-top:.55rem">استعلام گارانتی سریال</a>
  @auth
    <a class="wa-btn wa-btn-ghost wa-btn-block" href="{{ url('/account/serials') }}">سریال‌های من در کارتابل</a>
  @endauth
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
