@extends('layouts.storefront')
@section('title', $product->name)
@section('content')
@php
  use Plugins\Catalog\src\Support\StorefrontDisplaySettings;
  $ds = StorefrontDisplaySettings::get();
  $brandLabel = StorefrontDisplaySettings::brandLabel($ds);
  $gallery = $product->galleryUrls();
  $displaySerial = $product->displaySerialText();
  $hasW = $product->hasWarranty();
  $serials = $availableSerials ?? collect();
  $needsSerial = !empty($product->requires_serial);
  $inStock = $product->inStock() && ($product->stock_status ?? '') !== 'onbackorder';
  $specChips = array_filter([
    $product->capacity ?: null,
    $product->interface ?: null,
    $product->form_factor ?: null,
    $product->brand ?: null,
  ]);
  $rows = $product->specRows();
  $rawDesc = trim((string) ($product->description ?: ''));
  $descHasHtml = $rawDesc !== '' && $rawDesc !== strip_tags($rawDesc);
  $useCustomDd = !empty($ds['pdp_custom_serial_dropdown']);
  $compact = !empty($ds['pdp_compact_layout']);
  $fit = (($ds['pdp_image_fit'] ?? 'contain') === 'cover') ? 'cover' : 'contain';
  $mediaW = max(180, min(420, (int) ($ds['pdp_media_width'] ?? 280)));
@endphp
<section class="section hl-pdp {{ $compact ? 'hl-pdp--compact' : 'hl-pdp--roomy' }}" style="--hl-pdp-media-w: {{ $mediaW }}px; --hl-pdp-fit: {{ $fit }};">
  <div class="hl-pdp-hero">
    <div class="hl-pdp-media">
      <div class="hl-pdp-media-frame">
        <img id="main-image" class="hl-pdp-main" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="520" height="360">
      </div>
      @if(count($gallery) > 1)
        <div class="hl-pdp-thumbs">
          @foreach($gallery as $url)
            <button type="button" onclick="document.getElementById('main-image').src='{{ $url }}'">
              <img src="{{ $url }}" alt="" width="56" height="56">
            </button>
          @endforeach
        </div>
      @endif
    </div>

    <div class="hl-pdp-info">
      <div class="hl-pdp-kicker">
        @if(!empty($ds['pdp_show_brand']))
          <p class="hl-pdp-brand">{{ $brandLabel }}</p>
        @endif
        @if(!empty($ds['pdp_show_category']) && $product->category)
          <span class="hl-pdp-cat">
            @if($product->category->parent){{ $product->category->parent->name }} / @endif
            {{ $product->category->name }}
          </span>
        @endif
      </div>

      <h1>{{ $product->name }}</h1>

      @if(!empty($ds['pdp_show_lead']) && $product->short_description)
        <p class="hl-pdp-lead">{{ $product->short_description }}</p>
      @endif

      @if(!empty($ds['pdp_show_chips']) && $specChips)
        <div class="hl-pdp-chips">
          @foreach($specChips as $chip)
            <span>{{ $chip }}</span>
          @endforeach
        </div>
      @endif

      @if(!empty($ds['pdp_show_badges']))
        <div class="hl-pdp-badges">
          <span class="{{ $hasW ? 'ok' : 'none' }}">{{ $hasW ? 'گارانتی دارد' : 'فاقد گارانتی' }}</span>
          @if($hasW)<span class="info">{{ $product->warrantyBadgeText() }}</span>@endif
          <span>{{ $product->conditionLabel() }}</span>
          <span class="{{ $inStock ? 'ok' : 'none' }}">{{ $product->stockStatusLabel() }}</span>
        </div>
      @endif

      <div class="hl-pdp-buybar">
        <div class="hl-pdp-price">
          <strong>{{ $product->formattedPrice() }}</strong>
          @if($product->onSale())
            <s>{{ number_format($product->compare_price) }} تومان</s>
            <em>{{ $product->discountPercent() }}٪ تخفیف</em>
          @endif
        </div>

        @if(!empty($ds['pdp_show_display_serial']) && $displaySerial)
          <div class="hl-pdp-serial-note">
            <span>سریال نمایشی</span>
            <code>{{ $displaySerial }}</code>
            <a href="{{ url('/serial-check?serial='.urlencode($displaySerial)) }}">استعلام</a>
          </div>
        @endif

        @if(!empty($ds['pdp_show_stock_count']) && ($product->manage_stock ?? true))
          <p class="hl-pdp-stock">
            موجودی: <strong>{{ (int) $product->stock }}</strong>
            @if($product->availableSerialCount() > 0)
              · سریال آماده فروش: <strong>{{ $product->availableSerialCount() }}</strong>
            @endif
          </p>
        @endif

        @if($needsSerial && $serials->count() > 0)
          <div class="hl-serial-box">
            <h2>انتخاب سریال</h2>
            <p>{{ $serials->count() }} سریال آماده است. بعد از خرید در کارتابل شما می‌ماند.</p>
            <form method="post" action="{{ route('cart.add') }}" class="hl-serial-form" id="hlSerialForm">@csrf
              <input type="hidden" name="product_id" value="{{ $product->id }}">
              <input type="hidden" name="qty" value="1">
              @if($useCustomDd)
                <div class="hl-dd" data-hl-dd>
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
                <label class="hl-native-serial">
                  سریال قطعه
                  <select name="serial_id" required>
                    <option value="">انتخاب کنید…</option>
                    @foreach($serials as $sn)
                      <option value="{{ $sn->id }}">{{ $sn->serial }}</option>
                    @endforeach
                  </select>
                </label>
              @endif
              <div class="hl-pdp-actions">
                @if(!empty($ds['pdp_show_add_cart']))
                  <button class="hl-btn hl-btn-primary" type="submit">افزودن به سبد</button>
                @endif
                @if(!empty($ds['pdp_show_buy_now']))
                  <button class="hl-btn hl-btn-dark" type="submit" formaction="{{ route('cart.buy') }}">خرید سریع</button>
                @endif
                @if(!empty($ds['pdp_show_warranty_link']))
                  <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام گارانتی</a>
                @endif
              </div>
            </form>
          </div>
        @elseif($needsSerial)
          <div class="hl-pdp-oos-row">
            <span class="hl-pdp-oos">سریال فروش ثبت نشده</span>
            @if(!empty($ds['pdp_show_warranty_link']))
              <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام سریال</a>
            @endif
          </div>
        @else
          <div class="hl-pdp-actions">
            @if($inStock)
              @if(!empty($ds['pdp_show_add_cart']))
                <form method="post" action="{{ route('cart.add') }}" class="hl-qty-form">@csrf
                  <input type="hidden" name="product_id" value="{{ $product->id }}">
                  <input type="number" name="qty" value="1" min="1" @if($product->manage_stock ?? true) max="{{ max(1,$product->stock) }}" @endif>
                  <button class="hl-btn hl-btn-dark" type="submit">افزودن به سبد</button>
                </form>
              @endif
              @if(!empty($ds['pdp_show_buy_now']))
                <form method="post" action="{{ route('cart.buy') }}">@csrf
                  <input type="hidden" name="product_id" value="{{ $product->id }}">
                  <input type="hidden" name="qty" value="1">
                  <button class="hl-btn hl-btn-primary" type="submit">خرید سریع</button>
                </form>
              @endif
            @else
              <span class="hl-pdp-oos">ناموجود</span>
              @if(!empty($ds['pdp_show_preorder']))
                @auth
                  <a class="hl-btn hl-btn-primary" href="{{ route('account.preorders', ['product_id' => $product->id]) }}">پیش‌خرید</a>
                @else
                  <a class="hl-btn hl-btn-primary" href="{{ route('login') }}">ورود برای پیش‌خرید</a>
                @endauth
              @endif
            @endif
            @if(!empty($ds['pdp_show_warranty_link']))
              <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام سریال</a>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($ds['pdp_show_specs']) || !empty($ds['pdp_show_description']))
  <div class="hl-pdp-below">
    @if(!empty($ds['pdp_show_specs']))
      <div class="hl-pdp-panel">
        <h2>مشخصات فنی</h2>
        @if(count($rows))
          <div class="hl-spec-grid">
            @foreach($rows as $label => $value)
              <div class="hl-spec">
                <span>{{ $label }}</span>
                <strong>{{ $value }}</strong>
              </div>
            @endforeach
          </div>
        @else
          <p class="muted">مشخصات فنی ثبت نشده.</p>
        @endif
      </div>
    @endif

    @if(!empty($ds['pdp_show_description']))
      <div class="hl-pdp-panel">
        <h2>توضیحات بیشتر</h2>
        @if($rawDesc === '')
          <p class="muted">توضیحی ثبت نشده.</p>
        @elseif(!empty($ds['pdp_render_html_description']) && $descHasHtml)
          <div class="hl-pdp-prose">{!! \App\Support\SafeHtml::clean($rawDesc) !!}</div>
        @else
          <div class="hl-pdp-prose">{!! nl2br(e(strip_tags($rawDesc))) !!}</div>
        @endif
      </div>
    @endif
  </div>
  @endif

  @if(!empty($ds['pdp_show_related']) && $related->count())
    <div class="section-head" style="margin-top:1.25rem"><div><h2>محصولات مرتبط</h2></div></div>
    <div class="grid">
      @foreach($related as $item)
        @include('catalog::storefront.partials.product-card', ['product' => $item, 'compact' => true])
      @endforeach
    </div>
  @endif
</section>
@if($useCustomDd)
<script src="{{ asset('js/hl-select.js') }}?v=2" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('hlSerialForm');
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
