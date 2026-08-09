@extends('layouts.storefront')
@section('title', $product->name)
@section('content')
@php
  $gallery = $product->galleryUrls();
  $displaySerial = $product->displaySerialText();
  $hasW = $product->hasWarranty();
  $serials = $availableSerials ?? collect();
  $needsSerial = !empty($product->requires_serial);
  $specChips = array_filter([
    $product->capacity ?: null,
    $product->interface ?: null,
    $product->form_factor ?: null,
    $product->brand ?: null,
  ]);
@endphp
<section class="section hl-pdp">
  <div class="hl-pdp-hero">
    <div class="hl-pdp-media">
      <img id="main-image" class="hl-pdp-main" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="900" height="700">
      @if(count($gallery) > 1)
        <div class="hl-pdp-thumbs">
          @foreach($gallery as $url)
            <button type="button" onclick="document.getElementById('main-image').src='{{ $url }}'">
              <img src="{{ $url }}" alt="" width="72" height="72">
            </button>
          @endforeach
        </div>
      @endif
    </div>

    <div class="hl-pdp-info">
      <p class="hl-pdp-brand">سرزمین هارد</p>
      @if($product->category)
        <span class="hl-pdp-cat">
          @if($product->category->parent){{ $product->category->parent->name }} / @endif
          {{ $product->category->name }}
        </span>
      @endif
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
        <span>{{ $product->stockStatusLabel() }}</span>
      </div>

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
          <h2>انتخاب سریال برای خرید</h2>
          <p>{{ $serials->count() }} سریال موجود است. سریال انتخابی بعد از خرید در کارتابل شما ثبت می‌شود.</p>
          <form method="post" action="{{ route('cart.add') }}" class="hl-serial-form">@csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" value="1">
            <label>
              سریال قطعه
              <select name="serial_id" required>
                <option value="">— انتخاب کنید —</option>
                @foreach($serials as $sn)
                  <option value="{{ $sn->id }}">
                    {{ $sn->serial }}
                    @if(!empty($sn->warranty_company)) · {{ $sn->warranty_company }}@endif
                    @if(!empty($sn->company_warranty_months)) · {{ $sn->company_warranty_months }} ماه@endif
                  </option>
                @endforeach
              </select>
            </label>
            <div class="hl-pdp-actions">
              <button class="btn btn-dark" type="submit">افزودن به سبد با این سریال</button>
              <button class="btn btn-primary" type="submit" formaction="{{ route('cart.buy') }}">خرید همین سریال</button>
              <a class="btn btn-outline" href="{{ url('/serial-check') }}">استعلام گارانتی</a>
            </div>
          </form>
        </div>
      @elseif($needsSerial)
        <div class="alert alert-error">فعلاً سریال موجودی برای فروش ثبت نشده است.</div>
        <a class="btn btn-outline" href="{{ url('/serial-check') }}">استعلام سریال</a>
      @else
        <div class="hl-pdp-actions">
          @if($product->inStock() && ($product->stock_status ?? '') !== 'onbackorder')
            <form method="post" action="{{ route('cart.add') }}" class="hl-qty-form">@csrf
              <input type="hidden" name="product_id" value="{{ $product->id }}">
              <input type="number" name="qty" value="1" min="1" @if($product->manage_stock ?? true) max="{{ max(1,$product->stock) }}" @endif>
              <button class="btn btn-dark" type="submit">افزودن به سبد</button>
            </form>
            <form method="post" action="{{ route('cart.buy') }}">@csrf
              <input type="hidden" name="product_id" value="{{ $product->id }}">
              <input type="hidden" name="qty" value="1">
              <button class="btn btn-primary" type="submit">خرید سریع</button>
            </form>
          @else
            @auth
              <a class="btn btn-primary" href="{{ route('account.preorders', ['product_id' => $product->id]) }}">پیش‌خرید این قطعه</a>
            @else
              <a class="btn btn-primary" href="{{ route('login') }}">ورود برای پیش‌خرید</a>
            @endauth
            <div class="alert alert-error" style="margin:0">در حال حاضر ناموجود است</div>
          @endif
          <a class="btn btn-outline" href="{{ url('/serial-check') }}">استعلام سریال</a>
        </div>
      @endif
    </div>
  </div>

  <div class="hl-pdp-panel">
    <h2>مشخصات فنی</h2>
    @php $rows = $product->specRows(); @endphp
    @if(count($rows))
      <table class="specs-table">
        @foreach($rows as $label => $value)
          <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
      </table>
    @else
      <p class="muted">مشخصات فنی ثبت نشده.</p>
    @endif
  </div>

  <div class="hl-pdp-panel">
    <h2>توضیحات بیشتر</h2>
    <div>{!! nl2br(e($product->description ?: 'توضیحی ثبت نشده.')) !!}</div>
  </div>

  @if($related->count())
    <div class="section-head" style="margin-top:1.5rem"><div><h2>محصولات مرتبط</h2></div></div>
    <div class="grid">
      @foreach($related as $item)
        @include('catalog::storefront.partials.product-card', ['product' => $item, 'compact' => true])
      @endforeach
    </div>
  @endif
</section>
@endsection
