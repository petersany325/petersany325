@extends('web-app::layout')
@section('content')
@php $fmt = fn ($n) => number_format((int) $n); @endphp

<form class="wa-search" action="{{ url('/app/shop') }}" method="get">
  <input type="search" name="q" value="{{ $q }}" placeholder="جستجو در فروشگاه…" autocomplete="off">
  @if($cat !== '')<input type="hidden" name="cat" value="{{ $cat }}">@endif
  <button type="submit">جستجو</button>
</form>

@if($categories->isNotEmpty())
<div class="wa-cats wa-cats-wrap">
  <a class="wa-cat {{ $cat===''?'on':'' }}" href="{{ url('/app/shop'.($q!==''?'?q='.urlencode($q):'')) }}">همه</a>
  @foreach($categories as $c)
    <a class="wa-cat {{ $cat===($c->slug??'')?'on':'' }}" href="{{ url('/app/shop?cat='.urlencode($c->slug??'').($q!==''?'&q='.urlencode($q):'')) }}">{{ $c->name }}</a>
  @endforeach
</div>
@endif

<div class="wa-section-head">
  <strong>{{ $s['shop_title'] ?? 'فروشگاه' }}</strong>
  <span class="wa-muted">{{ $products->count() }} مورد</span>
</div>

<div class="wa-list">
  @forelse($products as $p)
    <a class="wa-list-item" href="{{ url('/app/product/'.($p->slug ?? $p->id)) }}">
      <div class="wa-list-thumb">
        @if(!empty($p->image))
          <img src="{{ \Plugins\WebApp\Plugin::productImageUrl($p->image ?? null) }}" alt="" loading="lazy">
        @endif
      </div>
      <div class="wa-list-body">
        <strong>{{ $p->name }}</strong>
        @if(!empty($p->brand))<small>{{ $p->brand }}</small>@endif
        <em>{{ $fmt($p->price ?? 0) }} تومان</em>
      </div>
    </a>
  @empty
    <div class="wa-empty">{{ $s['empty_products_text'] ?? 'موردی یافت نشد' }}</div>
  @endforelse
</div>
@endsection
