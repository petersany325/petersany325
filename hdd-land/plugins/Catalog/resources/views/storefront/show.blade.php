@extends('layouts.storefront')
@section('title', $product->name)
@section('content')
@php
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
@endphp
<section class="section hl-pdp">
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
        <p class="hl-pdp-brand">سرزمین هارد</p>
        @if($product->category)
          <span class="hl-pdp-cat">
            @if($product->category->parent){{ $product->category->parent->name }} / @endif
            {{ $product->category->name }}
          </span>
        @endif
      </div>

      <h1>{{ $product->name }}</h1>

      @if($product->short_description)
        <p class="hl-pdp-lead">{{ $product->short_description }}</p>
      @endif

      @if($specChips)
        <div class="hl-pdp-chips">
          @foreach($specChips as $chip)
            <span>{{ $chip }}</span>
          @endforeach
        </div>
      @endif

      <div class="hl-pdp-badges">
        <span class="{{ $hasW ? 'ok' : 'none' }}">{{ $hasW ? 'گارانتی دارد' : 'فاقد گارانتی' }}</span>
        @if($hasW)<span class="info">{{ $product->warrantyBadgeText() }}</span>@endif
        <span>{{ $product->conditionLabel() }}</span>
        <span class="{{ $inStock ? 'ok' : 'none' }}">{{ $product->stockStatusLabel() }}</span>
      </div>

      <div class="hl-pdp-buybar">
        <div class="hl-pdp-price">
          <strong>{{ $product->formattedPrice() }}</strong>
          @if($product->onSale())
            <s>{{ number_format($product->compare_price) }} تومان</s>
            <em>{{ $product->discountPercent() }}٪ تخفیف</em>
          @endif
        </div>

        @if($displaySerial)
          <div class="hl-pdp-serial-note">
            <span>سریال نمایشی</span>
            <code>{{ $displaySerial }}</code>
            <a href="{{ url('/serial-check?serial='.urlencode($displaySerial)) }}">استعلام</a>
          </div>
        @endif

        @if($product->manage_stock ?? true)
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
              <div class="hl-pdp-actions">
                <button class="hl-btn hl-btn-primary" type="submit">افزودن به سبد</button>
                <button class="hl-btn hl-btn-dark" type="submit" formaction="{{ route('cart.buy') }}">خرید سریع</button>
                <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام گارانتی</a>
              </div>
            </form>
          </div>
        @elseif($needsSerial)
          <div class="hl-pdp-oos-row">
            <span class="hl-pdp-oos">سریال فروش ثبت نشده</span>
            <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام سریال</a>
          </div>
        @else
          <div class="hl-pdp-actions">
            @if($inStock)
              <form method="post" action="{{ route('cart.add') }}" class="hl-qty-form">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="number" name="qty" value="1" min="1" @if($product->manage_stock ?? true) max="{{ max(1,$product->stock) }}" @endif>
                <button class="hl-btn hl-btn-dark" type="submit">افزودن به سبد</button>
              </form>
              <form method="post" action="{{ route('cart.buy') }}">@csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="qty" value="1">
                <button class="hl-btn hl-btn-primary" type="submit">خرید سریع</button>
              </form>
            @else
              <span class="hl-pdp-oos">ناموجود</span>
              @auth
                <a class="hl-btn hl-btn-primary" href="{{ route('account.preorders', ['product_id' => $product->id]) }}">پیش‌خرید</a>
              @else
                <a class="hl-btn hl-btn-primary" href="{{ route('login') }}">ورود برای پیش‌خرید</a>
              @endauth
            @endif
            <a class="hl-btn hl-btn-ghost" href="{{ url('/serial-check') }}">استعلام سریال</a>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="hl-pdp-below">
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

    <div class="hl-pdp-panel">
      <h2>توضیحات بیشتر</h2>
      @if($rawDesc === '')
        <p class="muted">توضیحی ثبت نشده.</p>
      @elseif($descHasHtml)
        <div class="hl-pdp-prose">{!! \App\Support\SafeHtml::clean($rawDesc) !!}</div>
      @else
        <div class="hl-pdp-prose">{!! nl2br(e($rawDesc)) !!}</div>
      @endif
    </div>
  </div>

  @if($related->count())
    <div class="section-head" style="margin-top:1.25rem"><div><h2>محصولات مرتبط</h2></div></div>
    <div class="grid">
      @foreach($related as $item)
        @include('catalog::storefront.partials.product-card', ['product' => $item, 'compact' => true])
      @endforeach
    </div>
  @endif
</section>
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
@endsection
