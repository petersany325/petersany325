@extends('accounting::layouts.account')
@section('title', $doc->number)
@section('content')
<header>
  <h1>{{ $doc->number }}</h1>
  <a href="{{ route('account.invoices') }}">لیست</a>
</header>
<div class="card">
  <div class="row">
    <div>
      <strong>{{ $types[$doc->type] ?? $doc->type }}</strong>
      <div class="meta">{{ $doc->doc_date }} · {{ $doc->status }}</div>
    </div>
    <div class="price">{{ number_format((int)$doc->total) }} تومان</div>
  </div>
</div>
<div class="card">
  <table>
    <thead><tr><th>قلم</th><th>تعداد</th><th>مبلغ</th></tr></thead>
    <tbody>
    @foreach($lines as $l)
      <tr>
        <td>{{ $l->title }}</td>
        <td>{{ $l->qty }}</td>
        <td>{{ number_format((int)$l->line_total) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@if($serials->count())
<div class="card">
  <strong>سریال‌های کالا</strong>
  <div class="meta" style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.35rem">
    @foreach($serials as $sn)<span class="badge">{{ $sn->serial }}</span>@endforeach
  </div>
</div>
@endif
@endsection
