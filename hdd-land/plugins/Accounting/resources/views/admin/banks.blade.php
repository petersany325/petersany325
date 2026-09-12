@extends('accounting::layouts.acc')
@section('title','بانک‌ها')
@section('content')
<div class="top"><div><h1>تعریف و مدیریت بانک</h1><p>افزودن، ویرایش و حذف حساب‌های بانکی</p></div></div>
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
<div class="panel"><div class="hd"><strong>حساب‌های بانکی</strong></div><div class="bd" style="padding:0">
@foreach($items as $b)
<form method="post" action="{{ route('admin.accounting.banks.update.post',$b->id) }}" class="form" style="padding:.75rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>نام بانک<input name="name" value="{{ $b->name }}" required></label>
    <label>شعبه<input name="branch" value="{{ $b->branch }}"></label>
  </div>
  <div class="row">
    <label>شماره حساب<input name="account_no" value="{{ $b->account_no }}"></label>
    <label>شبا<input name="iban" value="{{ $b->iban }}"></label>
  </div>
  <div class="row">
    <label>کارت<input name="card_no" value="{{ $b->card_no }}"></label>
    <label>صاحب حساب<input name="holder" value="{{ $b->holder }}"></label>
  </div>
  <div class="row">
    <label>موجودی اولیه<input name="opening_balance" value="{{ $b->opening_balance }}"></label>
    <label class="check" style="align-self:end"><input type="checkbox" name="is_active" value="1" @checked($b->is_active)> فعال</label>
  </div>
  <div class="actions">
    <button class="btn" type="submit">ذخیره</button>
    <button class="btn g" type="submit" formaction="{{ route('admin.accounting.banks.delete.post',$b->id) }}" onclick="return confirm('حذف/غیرفعال شود؟')">حذف</button>
  </div>
</form>
@endforeach
</div></div>
@endsection
