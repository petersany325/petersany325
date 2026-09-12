@extends('accounting::layouts.account')
@section('title', 'جزئیات اقساط')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<header>
  <h1>{{ $row->number }}</h1>
  <a href="{{ route('account.installments') }}">بازگشت</a>
</header>
@if(session('success'))<div class="card" style="border-color:#1f7a4c;color:#1f7a4c">{{ session('success') }}</div>@endif

<div class="card">
  <div class="row">
    <div>
      <strong>{{ $row->product_title }}</strong>
      <div class="meta">{{ $statuses[$row->status] ?? $row->status }} · {{ $row->months }} قسط</div>
    </div>
    <div class="price">{{ $m($row->monthly_amount) }}/ماه</div>
  </div>
  <div class="meta" style="margin-top:.7rem">
    قیمت {{ $m($row->product_price) }} · پیش‌پرداخت {{ $m($row->down_payment) }} · جمع {{ $m($row->total_amount) }}
  </div>
  @if($row->customer_note)
    <div class="meta" style="margin-top:.5rem">یادداشت شما: {{ $row->customer_note }}</div>
  @endif
  @if($row->admin_note)
    <div class="meta" style="margin-top:.5rem">پاسخ فروشگاه: {{ $row->admin_note }}</div>
  @endif
  @if($row->ticket_id)
    <div style="margin-top:.7rem"><a href="{{ url('/account/tickets') }}">مشاهده تیکت پشتیبانی #{{ $row->ticket_id }}</a></div>
  @else
    <div style="margin-top:.7rem"><a href="{{ url('/account/tickets') }}">پیگیری از تیکت پشتیبانی</a></div>
  @endif
</div>

@if($schedules->isNotEmpty())
<div class="card">
  <strong>جدول اقساط</strong>
  <table style="margin-top:.6rem">
    <thead><tr><th>#</th><th>سررسید</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
    <tbody>
    @foreach($schedules as $s)
      <tr>
        <td>{{ $s->installment_no }}</td>
        <td>{{ $s->due_date ?: '—' }}</td>
        <td>{{ $m($s->amount) }}</td>
        <td><span class="badge">{{ $s->status }}</span></td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
