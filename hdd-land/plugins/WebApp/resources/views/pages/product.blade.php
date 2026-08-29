@extends('web-app::layout')
@section('content')
@php
  use Plugins\Catalog\src\Support\StorefrontDisplaySettings;
  $ds = StorefrontDisplaySettings::get();
  $brandLabel = StorefrontDisplaySettings::brandLabel($ds);
  $fmt = fn ($n) => number_format((int) $n);
  $p = $product;
  $serials = $availableSerials ?? collect();
  $needsSerial = !empty($p->requires_serial);
  $hasW = !empty($p->has_warranty) || (!empty($p->warranty_type) && $p->warranty_type !== 'none') || !empty($p->warranty_months);
  $chips = array_filter([$p->capacity ?? null, $p->interface ?? null, $p->form_factor ?? null, $p->brand ?? null]);
  $useCustomDd = !empty($ds['wa_custom_serial_dropdown']);
  $rawDesc = trim((string) ($p->description ?? ''));
  $descHasHtml = $rawDesc !== '' && $rawDesc !== strip_tags($rawDesc);
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
  @if(!empty($ds['wa_show_brand']))
    <div class="wa-pdp-brand">{{ $brandLabel }}</div>
  @endif
  <h1>{{ $p->name }}</h1>

  @if(!empty($ds['wa_show_specs_chips']) && $chips)
    <div class="wa-tags">
      @foreach($chips as $chip)<span>{{ $chip }}</span>@endforeach
    </div>
  @elseif(!empty($ds['wa_show_specs_chips']) && (!empty($p->brand) || !empty($p->sku)))
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
  @elseif($rawDesc !== '')
    @if(!empty($ds['wa_render_html_description']) && $descHasHtml)
      <div class="wa-desc wa-desc-html">{!! \App\Support\SafeHtml::clean($rawDesc) !!}</div>
    @else
      <p class="wa-desc">{{ \Illuminate\Support\Str::limit(strip_tags($rawDesc), 280) }}</p>
    @endif
  @endif

  @if($needsSerial && $serials->count() > 0)
    <form method="post" action="{{ url('/app/cart/add') }}" class="wa-buy-box wa-serial-box" id="waSerialForm">
      @csrf
      <input type="hidden" name="product_id" value="{{ $p->id }}">
      <input type="hidden" name="qty" value="1">
      @if($useCustomDd)
        <div class="hl-dd wa-dd" data-hl-dd>
          <input type="hidden" name="serial_id" value="" required data-hl-dd-input>
          <button type="button" class="hl-dd-trigger" data-hl-dd-trigger aria-expanded="false">
            <span>
              <small>سریال قطعه</small>
              <strong data-hl-dd-label>انتخاب کنید…</strong>
            </span>
            <em class="hl-dd-caret" aria-hidden="true"></em>
          </button>
          <div class="hl-dd-panel" data-hl-dd-panel hidden>
            @foreach($serials as $sn)
              <button type="button" class="hl-dd-option" data-hl-dd-option data-value="{{ $sn->id }}" data-label="{{ $sn->serial }}">
                <strong dir="ltr">{{ $sn->serial }}</strong>
                <small>
                  @if(!empty($sn->warranty_company)){{ $sn->warranty_company }}@endif
                  @if(!empty($sn->company_warranty_months)) · {{ $sn->company_warranty_months }} ماه@endif
                </small>
              </button>
            @endforeach
          </div>
        </div>
      @else
        <label class="wa-native-serial">
          سریال قطعه
          <select name="serial_id" required>
            <option value="">انتخاب کنید…</option>
            @foreach($serials as $sn)
              <option value="{{ $sn->id }}">{{ $sn->serial }}</option>
            @endforeach
          </select>
        </label>
      @endif
      <p class="wa-serial-hint">سریال انتخابی در کارتابل «سریال‌ها و گارانتی من» می‌ماند.</p>
      <div class="wa-buy-actions">
        @if(!empty($s['product_show_add_cart']))
          <button class="wa-btn wa-btn-primary wa-btn-lg" type="submit">افزودن به سبد</button>
        @endif
        @if(!empty($s['product_show_buy_now']))
          <button class="wa-btn wa-btn-ghost wa-btn-lg" type="submit" name="buy_now" value="1">خرید سریع</button>
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
          <button class="wa-btn wa-btn-primary wa-btn-lg" type="submit">افزودن به سبد</button>
        @endif
        @if(!empty($s['product_show_buy_now']))
          <button class="wa-btn wa-btn-ghost wa-btn-lg" type="submit" name="buy_now" value="1">خرید سریع</button>
        @endif
      </div>
    </form>
  @endif

  <a class="wa-btn wa-btn-ghost wa-btn-block wa-btn-lg" href="{{ url('/serial-check') }}" style="margin-top:.55rem">استعلام گارانتی سریال</a>
  @auth
    <a class="wa-btn wa-btn-ghost wa-btn-block wa-btn-lg" href="{{ url('/account/serials') }}">سریال‌های من در کارتابل</a>
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

@if($useCustomDd)
<script src="{{ asset('js/hl-select.js') }}?v=2" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('waSerialForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      var input = form.querySelector('input[name="serial_id"]');
      if (!input || !input.value) {
        e.preventDefault();
        var dd = form.querySelector('[data-hl-dd]');
        if (dd) {
          dd.classList.add('is-invalid');
          var trigger = dd.querySelector('[data-hl-dd-trigger]');
          if (trigger) trigger.click();
        }
      }
    });
  });
</script>
@endif
@endsection
