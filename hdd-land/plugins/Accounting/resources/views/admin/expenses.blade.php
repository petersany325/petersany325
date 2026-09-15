@extends('accounting::layouts.acc')
@section('title','هزینه‌ها')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>تعریف و ثبت هزینه‌ها</h1><p>هزینه‌های عملیاتی با دسته و بانک پرداخت</p></div></div>
<div class="panel"><div class="hd"><strong>هزینه جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.expenses.store') }}" class="form">@csrf
  <div class="row">
    <label>عنوان<input name="title" required placeholder="اجاره / تبلیغات / ..."></label>
    <label>مبلغ (تومان)<input name="amount" required></label>
  </div>
  <div class="row">
    <label>دسته
      <select name="category_id">
        <option value="">—</option>
        @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
      </select>
    </label>
    <label>بانک پرداخت
      <select name="bank_id">
        <option value="">—</option>
        @foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>تاریخ<input type="date" name="doc_date" value="{{ now()->toDateString() }}"></label>
    <label>روش پرداخت<input name="payment_method"></label>
  </div>
  <label>یادداشت<textarea name="notes" rows="2"></textarea></label>
  <label class="check"><input type="checkbox" name="issue_now" value="1" checked> صدور فوری</label>
  <button class="btn" type="submit">ثبت هزینه</button>
</form>
</div></div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>عنوان</th><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th></tr></thead>
  <tbody>
  @forelse($items as $e)
    <tr>
      <td><a href="{{ route('admin.accounting.doc', $e->id) }}">{{ $e->number }}</a></td>
      <td>{{ $e->party_name }}</td>
      <td>{{ $e->doc_date }}</td>
      <td>{{ $money($e->total) }}</td>
      <td><span class="badge {{ $e->status }}">{{ $e->status }}</span></td>
    </tr>
  @empty
    <tr><td colspan="5">هزینه‌ای ثبت نشده.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
