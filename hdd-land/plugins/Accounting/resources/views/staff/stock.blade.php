@extends('accounting::layouts.acc', ['portal' => 'staff'])
@section('title', 'انبار')
@section('content')
<div class="acc-top"><div><h1>موجودی انبار</h1><p>نمای کارمندی از موجودی چندانباره</p></div></div>
<div class="acc-panel"><div class="bd" style="padding:0">
<table class="acc-table">
  <thead><tr><th>انبار</th><th>محصول</th><th>موجودی</th></tr></thead>
  <tbody>
  @forelse($balances as $b)
    <tr><td>{{ $b->warehouse_name }}</td><td>#{{ $b->product_id }}</td><td>{{ $b->qty }}</td></tr>
  @empty
    <tr><td colspan="3">موجودی خالی است.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
