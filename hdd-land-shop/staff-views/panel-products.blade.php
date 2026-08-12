@extends('layouts.staff')
@section('title','محصولات')
@section('content')
<div class="row" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
  <h1 style="margin:0">محصولات</h1>
  @if($canCreate)
    <a class="btn btn-primary" href="{{ url('/admin/products/create') }}">+ افزودن محصول</a>
  @endif
</div>
<p class="muted">ویرایش/حذف از طریق پنل محصولات (در صورت داشتن دسترسی).</p>
<div class="panel" style="overflow:auto;margin-top:.8rem">
  <table class="table">
    <thead><tr><th>نام</th><th>قیمت</th><th>خرید</th><th>موجودی</th><th></th></tr></thead>
    <tbody>
    @forelse($products as $p)
      <tr>
        <td>{{ $p->name }}</td>
        <td>{{ number_format((int)$p->price) }}</td>
        <td>{{ isset($p->cost_price) && $p->cost_price !== null ? number_format((int)$p->cost_price) : '—' }}</td>
        <td>{{ $p->stock }}</td>
        <td class="row" style="gap:.35rem">
          @if($canEdit)
            <a class="btn btn-outline btn-sm" href="{{ url('/admin/products/'.$p->id.'/edit') }}">ویرایش</a>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted" style="text-align:center;padding:1rem">محصولی نیست.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
