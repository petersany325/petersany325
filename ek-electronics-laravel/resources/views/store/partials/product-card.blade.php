<article class="product">
  <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="600" height="450">
  <div class="product-body">
    <div class="cat">{{ $product->category?->name }}</div>
    <strong>{{ $product->name }}</strong>
    <div>
      @if ($product->grade)<span class="grade">{{ $product->grade }}</span>@endif
      <span class="muted"> · {{ $product->stock }} in stock</span>
    </div>
    <div class="price">R {{ number_format((float) $product->price, 2) }}</div>
    <div class="cta-row">
      <form method="post" action="{{ route('cart.add') }}">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <button class="btn btn-primary" type="submit">Add to cart</button>
      </form>
      <a class="btn btn-outline" href="https://wa.me/{{ $settings['whatsapp'] ?? '27105002140' }}?text={{ urlencode('Hi EK Electronics, I want to order '.$product->name.' ('.$product->sku.').') }}">WhatsApp</a>
    </div>
  </div>
</article>
