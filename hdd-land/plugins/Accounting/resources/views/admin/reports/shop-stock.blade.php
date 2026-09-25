@extends('accounting::layouts.acc')
@section('title','موجودی فروشگاه')
@section('content')
<div class="top">
  <div>
    <h1>تطبیق موجودی سایت و انبار</h1>
    <p>فروش صادرشده از سایت و فاکتور حسابداری باید موجودی را یکسان کم کند</p>
  </div>
  <a class="btn g" href="{{ url('/admin/accounting/goods') }}">تعریف کالا</a>
</div>
<div class="panel"><div class="bd" style="padding:0;overflow:auto">
<table>
  <thead><tr><th>کد</th><th>نام</th><th>موجودی سایت</th><th>موجودی انبار</th><th>فروش صادرشده</th><th>اختلاف سایت−انبار</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->sku ?: '—' }}</td>
      <td>{{ $r->name }}</td>
      <td>{{ $r->site }}</td>
      <td>{{ $r->warehouse }}</td>
      <td>{{ $r->sold }}</td>
      <td>@if(abs($r->diff)>0.001)<span class="badge cancelled">{{ $r->diff }}</span>@else<span class="badge issued">۰</span>@endif</td>
    </tr>
  @empty
    <tr><td colspan="6">کالایی برای تطبیق نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
