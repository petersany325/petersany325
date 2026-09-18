@extends('accounting::layouts.acc')
@section('title','حقوق و دستمزد')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>حقوق و دستمزد کارمندان</h1><p>محاسبه حقوق پایه + کمیسیون فروش دوره</p></div></div>
<div class="panel"><div class="hd"><strong>اجرای حقوق جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.payroll.store') }}" class="form">@csrf
  <label>دوره (YYYY-MM)<input name="period" value="{{ $period }}" required></label>
  <p style="color:var(--muted);font-size:.85rem">{{ $staff->count() }} کارمند فعال — حقوق پایه و درصد کمیسیون از پرونده کارمند خوانده می‌شود.</p>
  <button class="btn" type="submit">محاسبه لیست حقوق</button>
</form>
</div></div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>دوره</th><th>وضعیت</th><th>پایه</th><th>کمیسیون</th><th>خالص</th><th></th></tr></thead>
  <tbody>
  @forelse($runs as $r)
    <tr>
      <td>{{ $r->period }}</td>
      <td><span class="badge">{{ $r->status }}</span></td>
      <td>{{ $money($r->total_base) }}</td>
      <td>{{ $money($r->total_commission) }}</td>
      <td>{{ $money($r->total_net) }}</td>
      <td><a href="{{ route('admin.accounting.payroll.show', $r->id) }}">جزئیات</a></td>
    </tr>
  @empty
    <tr><td colspan="6">هنوز لیست حقوقی ساخته نشده.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
