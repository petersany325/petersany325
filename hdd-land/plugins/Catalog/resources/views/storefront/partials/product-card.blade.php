{{-- کادر محصول فروشگاهی — تنظیمات از ادمین روی هر محصول --}}
@php
  /** @var \Plugins\Catalog\src\Models\Product $product */
  $compact = $compact ?? false;
  $cs = $product->cardSettings();
  $serial = $product->displaySerialText();
  $showSerial = !empty($cs['show_serial']) && ($product->show_serial_on_card ?? true) && $serial;
  $hasW = $product->hasWarranty();
  $serialCount = $product->availableSerialCount();
  $warrantyTypes = \Plugins\Catalog\src\Models\Product::WARRANTY_TYPES;
@endphp
<article class="shop-card {{ $compact ? 'shop-card--compact' : '' }}">
  <a class="shop-card__media" href="{{ route('products.show', $product->slug) }}" aria-label="{{ $product->name }}">
    <span class="shop-card__img-wrap">
      <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="400" height="400" loading="lazy">
    </span>
    @if($product->onSale())
      <span class="shop-card__ribbon">{{ $product->discountPercent() }}٪</span>
    @endif
    @if(!empty($cs['show_stock']))
      <span class="shop-card__stock shop-card__stock--{{ $product->stock_status ?: 'instock' }}">
        {{ $product->stockStatusLabel() }}
        @if($product->manage_stock ?? true)
          · {{ (int) $product->stock }} عدد
        @endif
      </span>
    @endif
  </a>

  <div class="shop-card__body">
    @if(!empty($cs['show_meta']))
      <div class="shop-card__meta">
        @if($product->brand)<span>{{ $product->brand }}</span>@endif
        @if($product->capacity)<span>{{ $product->capacity }}</span>@endif
        @if($product->part_type)<span>{{ $product->partTypeLabel() }}</span>@endif
      </div>
    @endif

    <h3 class="shop-card__title">
      <a href="{{ route('products.show', $product->slug) }}">{{ $product->name ?: 'نام محصول' }}</a>
    </h3>

    @if(!$compact && !empty($cs['show_short_desc']) && $product->short_description)
      <p class="shop-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($product->short_description), 90) }}</p>
    @endif

    @if(!empty($cs['show_warranty']) || !empty($cs['show_condition']))
      <div class="shop-card__badges">
        @if(!empty($cs['show_warranty']))
          <span class="shop-badge {{ $hasW ? 'shop-badge--ok' : 'shop-badge--none' }}">
            {{ $hasW ? 'گارانتی دارد' : 'فاقد گارانتی' }}
          </span>
          @if($hasW)
            <span class="shop-badge shop-badge--info">{{ $product->warrantyBadgeText() }}</span>
          @endif
        @endif
        @if(!empty($cs['show_condition']) && $product->condition)
          <span class="shop-badge">{{ $product->conditionLabel() }}</span>
        @endif
      </div>
    @endif

    @if($showSerial)
      <div class="shop-card__serial">
        <span>سریال</span>
        <code>{{ $serial }}</code>
        @if($serialCount > 0)
          <small>{{ $serialCount }} سریال موجود</small>
        @endif
      </div>
    @elseif(!empty($cs['show_serial']) && $serialCount > 0)
      <div class="shop-card__serial">
        <span>سریال آماده</span>
        <small>{{ $serialCount }} عدد در انبار سریال</small>
      </div>
    @endif

    <div class="shop-card__price-row">
      <div class="shop-card__price">{{ $product->formattedPrice() }}</div>
      @if($product->onSale())
        <div class="shop-card__old">{{ number_format($product->compare_price) }} تومان</div>
      @endif
    </div>

    <div class="shop-card__actions">
      @if($product->inStock() && ($product->stock_status ?? '') !== 'onbackorder')
        @if(!empty($cs['show_add_cart']))
          <form method="post" action="{{ route('cart.add') }}" class="shop-card__form">@csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn-dark btn-sm" type="submit">افزودن به سبد</button>
          </form>
        @endif
        @if(!empty($cs['show_buy_now']))
          <form method="post" action="{{ route('cart.buy') }}" class="shop-card__form">@csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn-primary btn-sm" type="submit">پرداخت / خرید</button>
          </form>
        @endif
      @elseif(!empty($cs['show_preorder']) && (($product->stock_status ?? '') === 'onbackorder' || $product->allowsPreorder()))
        @auth
          <a class="btn btn-primary btn-sm" href="{{ route('account.preorders', ['product_id' => $product->id]) }}">پیش‌خرید قطعه</a>
        @else
          <a class="btn btn-primary btn-sm" href="{{ route('login') }}">ورود برای پیش‌خرید</a>
        @endauth
        <a class="btn btn-outline btn-sm" href="{{ route('products.show', $product->slug) }}">جزئیات بیشتر</a>
      @else
        <a class="btn btn-outline btn-sm" href="{{ route('products.show', $product->slug) }}">جزئیات بیشتر</a>
      @endif
    </div>
  </div>
</article>
