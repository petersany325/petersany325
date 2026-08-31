@extends('layouts.storefront')
@section('title', isset($category) ? $category->name : 'محصولات')
@section('content')
<section class="section">
  <div class="section-head">
    <div>
      <h2>{{ isset($category) ? $category->name : 'همه محصولات' }}</h2>
      <p>
        {{ $products->total() }} محصول
        @if(method_exists($products, 'lastPage') && $products->lastPage() > 1)
          <span class="muted">· صفحه {{ $products->currentPage() }} از {{ $products->lastPage() }}</span>
        @endif
      </p>
    </div>
  </div>

  @if(isset($category) && $category->activeChildren->count())
    <div class="subcats" style="margin-bottom:1rem">
      @foreach($category->activeChildren as $child)
        <a class="subcat-chip" href="{{ route('categories.show', $child->slug) }}">{{ $child->name }}</a>
      @endforeach
    </div>
  @endif

  <form class="panel filters" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی نام، برند، SKU یا سریال...">
    <select name="category">
      <option value="">همه دسته‌ها</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->slug }}" @selected(request('category')===$cat->slug || (isset($category) && $category->id===$cat->id))>{{ $cat->name }}</option>
        @foreach($cat->activeChildren as $child)
          <option value="{{ $child->slug }}" @selected(request('category')===$child->slug || (isset($category) && $category->id===$child->id))>— {{ $child->name }}</option>
        @endforeach
      @endforeach
    </select>
    <select name="part_type">
      <option value="">نوع قطعه</option>
      @foreach(\Plugins\Catalog\src\Models\Product::PART_TYPES as $key => $label)
        <option value="{{ $key }}" @selected(request('part_type')===$key)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="brand">
      <option value="">برند</option>
      @foreach(($brands ?? []) as $brand)
        <option value="{{ $brand }}" @selected(request('brand')===$brand)>{{ $brand }}</option>
      @endforeach
    </select>
    <select name="warranty">
      <option value="">گارانتی</option>
      <option value="yes" @selected(request('warranty')==='yes')>دارای گارانتی</option>
      <option value="no" @selected(request('warranty')==='no')>فاقد گارانتی</option>
    </select>
    <button class="btn btn-primary" type="submit">جستجو</button>
  </form>

  <div class="grid">
    @forelse($products as $product)
      @include('catalog::storefront.partials.product-card', ['product' => $product])
    @empty
      <div class="panel">محصولی یافت نشد.</div>
    @endforelse
  </div>
  @if(method_exists($products, 'hasPages') && $products->hasPages())
    <div class="panel" style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between">
      <span class="muted" style="font-size:.9rem">ادامه محصولات در صفحات بعد</span>
      <div>{{ $products->links() }}</div>
    </div>
  @else
    <div style="margin-top:1rem">{{ $products->links() }}</div>
  @endif
</section>
@endsection
