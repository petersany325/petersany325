@extends('accounting::layouts.acc')
@section('title',$doc->number)
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>{{ $types[$doc->type] ?? $doc->type }} — {{ $doc->number }}</h1>
    <p>{{ $doc->doc_date }} · <span class="badge {{ $doc->status }}">{{ $doc->status }}</span></p>
  </div>
  <div class="actions">
    @if($doc->status==='draft')
      <form method="post" action="{{ route('admin.accounting.doc.issue',$doc->id) }}">@csrf
        <button class="btn o" type="submit">صدور سند</button>
      </form>
    @endif
    @if($doc->type==='proforma' && $doc->status!=='converted')
      <form method="post" action="{{ route('admin.accounting.doc.convert',$doc->id) }}">@csrf
        <button class="btn w" type="submit">تبدیل به فاکتور فروش</button>
      </form>
    @endif
    <a class="btn g" href="{{ route('admin.accounting.docs') }}">لیست</a>
  </div>
</div>
<div class="grid">
  <div class="card"><h3>طرف حساب</h3><div class="v" style="font-size:1rem">{{ $doc->party_name ?: '—' }}</div></div>
  <div class="card"><h3>جمع جزء</h3><div class="v">{{ $m($doc->subtotal) }}</div></div>
  <div class="card"><h3>تخفیف / مالیات</h3><div class="v" style="font-size:1rem">{{ $m($doc->discount) }} / {{ $m($doc->tax) }}</div></div>
  <div class="card"><h3>مبلغ نهایی</h3><div class="v">{{ $m($doc->total) }}</div>
    @if($doc->commission_amount)<div class="s">کمیسیون ٪{{ $doc->commission_rate }} = {{ $m($doc->commission_amount) }}</div>@endif
  </div>
</div>
<div class="panel"><div class="hd"><strong>اقلام</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>عنوان</th><th>تعداد</th><th>فی</th><th>جمع</th></tr></thead>
  <tbody>
  @foreach($lines as $l)
    <tr>
      <td>{{ $l->title }}@if($l->sku)<div style="color:var(--muted);font-size:.8rem">{{ $l->sku }}</div>@endif</td>
      <td>{{ $l->qty }}</td>
      <td>{{ $m($l->unit_price) }}</td>
      <td>{{ $m($l->line_total) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@if($serials->count())
<div class="panel"><div class="hd"><strong>سریال‌ها</strong></div><div class="bd">
  <div class="chips">@foreach($serials as $sn)<span class="badge">{{ $sn->serial }}</span>@endforeach</div>
</div></div>
@endif
@if($doc->notes)
<div class="panel"><div class="bd"><strong>یادداشت:</strong> {{ $doc->notes }}</div></div>
@endif
@endsection
