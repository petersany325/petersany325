@extends('accounting::layouts.acc', ['portal' => 'staff'])
@section('title', $doc->number)
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="acc-top"><div><h1>{{ $doc->number }}</h1><p>{{ $types[$doc->type] ?? $doc->type }} · {{ $doc->status }}</p></div>
<a class="acc-btn ghost" href="{{ route('staff.accounting.docs') }}">بازگشت</a></div>
<div class="acc-card" style="margin-bottom:1rem"><h3>مبلغ</h3><div class="val">{{ $money($doc->total) }}</div><div class="sub">{{ $doc->party_name }}</div></div>
<div class="acc-panel"><div class="bd" style="padding:0">
<table class="acc-table">
  <thead><tr><th>عنوان</th><th>تعداد</th><th>فی</th><th>جمع</th></tr></thead>
  <tbody>
  @foreach($lines as $l)
    <tr><td>{{ $l->title }}</td><td>{{ $l->qty }}</td><td>{{ $money($l->unit_price) }}</td><td>{{ $money($l->line_total) }}</td></tr>
  @endforeach
  </tbody>
</table>
</div></div>
@if($serials->count())
<div class="acc-panel"><div class="hd"><strong>سریال‌ها</strong></div><div class="bd">
@foreach($serials as $sn)<span class="acc-badge">{{ $sn->serial }}</span> @endforeach
</div></div>
@endif
@endsection
