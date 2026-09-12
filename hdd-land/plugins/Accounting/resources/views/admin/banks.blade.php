@extends('accounting::layouts.acc')
@section('title','بانک‌ها')
@section('content')
<div class="top"><div><h1>تعریف بانک</h1><p>حساب‌های بانکی برای دریافت و پرداخت</p></div></div>
<div class="panel"><div class="hd"><strong>حساب بانکی جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.banks.store') }}" class="form">@csrf
  <div class="row">
    <label>نام بانک<input name="name" required></label>
    <label>شعبه<input name="branch"></label>
  </div>
  <div class="row">
    <label>شماره حساب<input name="account_no"></label>
    <label>شبا<input name="iban" placeholder="IR..."></label>
  </div>
  <div class="row">
    <label>کارت<input name="card_no"></label>
    <label>صاحب حساب<input name="holder"></label>
  </div>
  <label>موجودی اولیه (تومان)<input name="opening_balance" value="0"></label>
  <button class="btn" type="submit">ثبت بانک</button>
</form>
</div></div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>بانک</th><th>شعبه</th><th>حساب</th><th>شبا</th><th>موجودی اولیه</th></tr></thead>
  <tbody>
  @foreach($items as $b)
    <tr>
      <td>{{ $b->name }}</td>
      <td>{{ $b->branch ?: '—' }}</td>
      <td>{{ $b->account_no ?: '—' }}</td>
      <td>{{ $b->iban ?: '—' }}</td>
      <td>{{ number_format((int)$b->opening_balance) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
</div></div>
@endsection
