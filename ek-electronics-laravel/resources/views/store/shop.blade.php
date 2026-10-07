@extends('layouts.store')
@section('title', 'Shop | EK Electronics')
@section('content')
<section class="wrap section">
  <h2>Products</h2>
  <p class="lede">Search the catalogue, filter by category, and add certified hardware to cart. Checkout opens a structured WhatsApp order for the sales desk.</p>
  <div class="shop-layout">
    <aside class="filters card">
      <h3>Shop by category</h3>
      <div class="filter-list">
        <a class="{{ !request('category') ? 'active' : '' }}" href="{{ route('shop', request()->except('category')) }}" style="display:block;padding:8px 10px;border-radius:10px">All categories</a>
        @foreach ($categories as $category)
          <a class="{{ request('category') === $category->slug ? 'active' : '' }}" href="{{ route('shop', array_merge(request()->query(), ['category' => $category->slug])) }}" style="display:block;padding:8px 10px;border-radius:10px;color:{{ request('category') === $category->slug ? 'var(--blue-deep)' : 'var(--muted)' }};background:{{ request('category') === $category->slug ? 'var(--blue-soft)' : 'transparent' }}">{{ $category->name }}</a>
        @endforeach
      </div>
    </aside>
    <div>
      <div class="toolbar-row">
        <div class="muted">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} results</div>
        <form method="get">
          @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
          <input name="q" value="{{ request('q') }}" type="search" placeholder="Filter this page…" style="border:1px solid var(--line);border-radius:999px;padding:8px 14px;min-inline-size:220px">
        </form>
      </div>
      <div class="grid-3">
        @foreach ($products as $product)
          @include('store.partials.product-card', ['product' => $product])
        @endforeach
      </div>
      <div style="margin-top:24px">{{ $products->links() }}</div>
    </div>
  </div>
</section>
@endsection
