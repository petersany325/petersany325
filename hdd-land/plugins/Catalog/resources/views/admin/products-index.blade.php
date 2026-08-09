@extends('layouts.admin')
@section('title','محصولات')
@section('content')
<div class="row" style="justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem">
  <h1 style="margin:0">محصولات</h1>
  <div class="row" style="gap:.4rem">
    <a class="btn btn-outline" href="{{ url('/admin/products/display-settings') }}">تنظیمات نمایش محصول</a>
    <a class="btn btn-primary" href="{{ route('admin.products.create') }}">افزودن محصول</a>
  </div>
</div>

<form class="panel filters" method="get" style="grid-template-columns:1.4fr 1fr 1fr 1fr auto">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="جستجو نام / SKU / برند">
  <select name="status">
    <option value="">همه وضعیت‌ها</option>
    @foreach(\Plugins\Catalog\src\Models\Product::STATUSES as $key=>$label)
      <option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="stock_status">
    <option value="">وضعیت انبار</option>
    @foreach(\Plugins\Catalog\src\Models\Product::STOCK_STATUSES as $key=>$label)
      <option value="{{ $key }}" @selected(request('stock_status')===$key)>{{ $label }}</option>
    @endforeach
  </select>
  <select name="category_id">
    <option value="">همه دسته‌ها</option>
    @foreach($categories as $cat)
      <option value="{{ $cat->id }}" @selected(request('category_id')==$cat->id)>{{ $cat->name }}</option>
    @endforeach
  </select>
  <button class="btn btn-primary" type="submit">فیلتر</button>
</form>

<div class="panel">
<table class="table">
<thead>
<tr>
  <th>تصویر</th>
  <th>نام</th>
  <th>SKU</th>
  <th>موجودی</th>
  <th>خرید</th>
  <th>فروش</th>
  <th>سود</th>
  <th>وضعیت</th>
  <th></th>
</tr>
</thead>
<tbody>
@forelse($products as $product)
@php
  $buy = (int) ($product->cost_price ?? 0);
  $sell = (int) $product->price;
  $unitProfit = $sell - $buy;
  $margin = $sell > 0 ? round(($unitProfit / $sell) * 100, 1) : 0;
@endphp
<tr>
  <td style="width:64px"><img src="{{ $product->imageUrl() }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid var(--line)"></td>
  <td>
    <strong>{{ $product->name }}</strong>
    <div class="muted">
      @if($product->category)
        @if($product->category->parent){{ $product->category->parent->name }} / @endif
        {{ $product->category->name }}
      @else بدون دسته @endif
      @if($product->is_featured) · ویژه @endif
    </div>
  </td>
  <td>{{ $product->sku ?: '—' }}</td>
  <td>
    <span class="badge" style="{{ ($product->stock_status??'')==='outofstock' ? 'background:#fff1f0;color:#a32012' : '' }}">
      {{ $product->stockStatusLabel() }}
    </span>
    <div class="muted">{{ $product->stock }}</div>
  </td>
  <td>{{ $buy > 0 ? number_format($buy) : '—' }}</td>
  <td>
    {{ number_format($sell) }}
    @if($product->onSale())
      <div class="muted"><s>{{ number_format($product->compare_price) }}</s> · {{ $product->discountPercent() }}٪</div>
    @endif
  </td>
  <td>
    @if($buy > 0)
      <strong style="color:{{ $unitProfit>=0?'#047857':'#a32012' }}">{{ number_format($unitProfit) }}</strong>
      <div class="muted">{{ $margin }}٪</div>
    @else
      <span class="muted">بدون قیمت خرید</span>
    @endif
  </td>
  <td>{{ $product->statusLabel() }}</td>
  <td class="row" style="gap:.3rem;flex-wrap:wrap">
    <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit',$product) }}">ویرایش</a>
    <a class="btn btn-dark btn-sm" href="{{ url('/admin/products/'.$product->id.'/serials') }}">سریال‌ها</a>
    <form method="post" action="{{ route('admin.products.destroy',$product) }}" onsubmit="return confirm('حذف شود؟')">@csrf @method('DELETE')
      <button class="btn btn-outline btn-sm" type="submit">حذف</button>
    </form>
  </td>
</tr>
@empty
<tr><td colspan="9">محصولی نیست.</td></tr>
@endforelse
</tbody>
</table>
{{ $products->links() }}
</div>
@endsection
