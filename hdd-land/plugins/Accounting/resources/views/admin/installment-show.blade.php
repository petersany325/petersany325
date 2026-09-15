@extends('accounting::layouts.acc')
@section('title','جزئیات اقساط '.$row->number)
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>درخواست {{ $row->number }}</h1>
    <p>{{ $row->customer_name }} · {{ $statuses[$row->status] ?? $row->status }}</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ route('admin.accounting.installments') }}">بازگشت</a>
  </div>
</div>

<div class="grid">
  <div class="card"><h3>قیمت کالا</h3><div class="v">{{ $m($row->product_price) }}</div></div>
  <div class="card"><h3>پیش‌پرداخت</h3><div class="v">{{ $m($row->down_payment) }}</div></div>
  <div class="card"><h3>قسط ماهانه</h3><div class="v">{{ $m($row->monthly_amount) }}</div></div>
  <div class="card"><h3>تعداد</h3><div class="v">{{ $row->months }}</div></div>
</div>

<div class="panel"><div class="hd"><strong>جزئیات</strong></div><div class="bd">
  <p><strong>کالا:</strong> {{ $row->product_title }}</p>
  <p><strong>موبایل:</strong> {{ $row->customer_mobile ?: '—' }} · <strong>کد ملی:</strong> {{ $row->customer_national_id ?: '—' }}</p>
  @if($user)<p><strong>کاربر سیستم:</strong> {{ $user->name }} (#{{ $user->id }})</p>@endif
  @if($row->ticket_id)<p><strong>تیکت:</strong> #{{ $row->ticket_id }}</p>@endif
  @if($row->customer_note)<p><strong>یادداشت مشتری:</strong> {{ $row->customer_note }}</p>@endif
</div></div>

<div class="panel"><div class="hd"><strong>تغییر وضعیت</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.installments.status', $row->id) }}" class="form">@csrf
  <div class="row">
    <label>وضعیت
      <select name="status">
        @foreach($statuses as $k=>$lab)
          <option value="{{ $k }}" @selected($row->status===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
    <label>قسط ماهانه (در تأیید)<input name="monthly_amount" value="{{ $row->monthly_amount }}"></label>
  </div>
  <label>یادداشت ادمین<textarea name="admin_note" rows="2">{{ $row->admin_note }}</textarea></label>
  <button class="btn" type="submit">ذخیره وضعیت</button>
</form>
</div></div>

<div class="panel"><div class="hd"><strong>جدول اقساط</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>#</th><th>سررسید</th><th>مبلغ</th><th>وضعیت</th><th>پرداخت</th><th></th></tr></thead>
  <tbody>
  @forelse($schedules as $s)
    <tr>
      <td>{{ $s->installment_no }}</td>
      <td>{{ $s->due_date ?: '—' }}</td>
      <td>{{ $m($s->amount) }}</td>
      <td><span class="badge">{{ $s->status }}</span></td>
      <td>{{ $s->paid_at ?: '—' }} @if($s->paid_amount)( {{ $m($s->paid_amount) }} )@endif</td>
      <td>
        <form method="post" action="{{ route('admin.accounting.installments.schedule', [$row->id, $s->id]) }}" style="display:flex;gap:.35rem;align-items:center">
          @csrf
          <input type="hidden" name="status" value="paid">
          <input name="paid_amount" value="{{ $s->amount }}" style="width:110px;border:1px solid var(--line);border-radius:10px;padding:.35rem">
          <button class="btn" type="submit">پرداخت</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td colspan="6">پس از تأیید/فعال‌سازی، جدول اقساط ساخته می‌شود.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>

<form method="post" action="{{ route('admin.accounting.installments.delete', $row->id) }}" onsubmit="return confirm('حذف کامل؟')">
  @csrf
  <button class="btn g" type="submit">حذف درخواست</button>
</form>
@endsection
