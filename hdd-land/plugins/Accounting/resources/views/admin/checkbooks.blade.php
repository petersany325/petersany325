@extends('accounting::layouts.acc')
@section('title','دسته چک')
@section('content')
<div class="top">
  <div>
    <h1>تعریف دسته چک</h1>
    <p>تعداد برگ دسته برای شرکت یا شخص، سری برگ‌ها و پنجره اخطار سررسید</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ url('/admin/accounting/checks/received') }}">چک دریافتی</a>
    <a class="btn g" href="{{ url('/admin/accounting/checks/spent') }}">چک خرج‌شده</a>
  </div>
</div>
<div class="panel"><div class="hd"><strong>دسته جدید</strong></div><div class="bd">
<form method="post" action="{{ url('/admin/accounting/checkbooks') }}" class="form">@csrf
  <div class="row">
    <label>صاحب دسته
      <select name="owner_type">
        <option value="company">شرکت</option>
        <option value="person">شخص</option>
      </select>
    </label>
    <label>نام صاحب<input name="owner_name" required placeholder="HDD Land / نام شخص"></label>
  </div>
  <div class="row">
    <label>بانک (متن)<input name="bank_name"></label>
    <label>حساب بانکی سیستم
      <select name="bank_id">
        <option value="">—</option>
        @foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>از شماره<input name="series_from"></label>
    <label>تا شماره<input name="series_to"></label>
  </div>
  <div class="row">
    <label>تعداد برگ<input name="leaf_count" type="number" min="1" required></label>
    <label>اخطار سررسید (روز)<input name="alert_days" type="number" min="1" max="90" value="{{ $defaultDays }}"></label>
  </div>
  <label>یادداشت<input name="notes"></label>
  <button class="btn" type="submit">ثبت دسته</button>
</form>
</div></div>
<div class="panel"><div class="hd"><strong>دسته‌های ثبت‌شده</strong></div><div class="bd" style="padding:0">
@forelse($items as $b)
<form method="post" action="{{ url('/admin/accounting/checkbooks/'.$b->id.'/update') }}" class="form" style="padding:.85rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>صاحب
      <select name="owner_type">
        <option value="company" @selected($b->owner_type==='company')>شرکت</option>
        <option value="person" @selected($b->owner_type==='person')>شخص</option>
      </select>
    </label>
    <label>نام<input name="owner_name" value="{{ $b->owner_name }}"></label>
  </div>
  <div class="row">
    <label>بانک<input name="bank_name" value="{{ $b->bank_name }}"></label>
    <label>تعداد برگ<input name="leaf_count" value="{{ $b->leaf_count }}"></label>
  </div>
  <div class="row">
    <label>مصرف‌شده<input name="used_count" value="{{ $b->used_count }}"></label>
    <label>اخطار (روز)<input name="alert_days" value="{{ $b->alert_days }}"></label>
  </div>
  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($b->is_active)> فعال — باقی‌مانده {{ max(0, $b->leaf_count - $b->used_count) }} برگ</label>
  <button class="btn" type="submit">ذخیره</button>
</form>
@empty
  <div style="padding:1rem">دسته‌ای ثبت نشده.</div>
@endforelse
</div></div>
@endsection
