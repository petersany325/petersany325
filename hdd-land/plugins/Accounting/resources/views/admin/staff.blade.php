@extends('accounting::layouts.acc')
@section('title','کارمند و ویزیتور')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>تعریف کارمند و ویزیتور</h1>
    <p>حقوق پایه، کمیسیون فروش و درصد سود خالص فروش اینجا تعریف می‌شود — منوی کمیسیون جدا حذف شد.</p>
  </div>
  <a class="btn g" href="{{ url('/admin/accounting/payroll') }}">لیست حقوق</a>
</div>
<div class="panel"><div class="hd"><strong>ثبت پرونده جدید</strong></div><div class="bd">
<form method="post" action="{{ url('/admin/accounting/staff') }}" class="form">@csrf
  <div class="row">
    <label>نام<input name="name" required></label>
    <label>نوع
      <select name="kind">
        @foreach($kinds as $k=>$lab)<option value="{{ $k }}">{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>نقش / سمت<input name="role" placeholder="فروشنده / ویزیتور / انبار"></label>
    <label>تلفن<input name="phone"></label>
  </div>
  <div class="row">
    <label>حقوق پایه (تومان)<input name="base_salary" value="0"></label>
    <label>٪ کمیسیون فروش<input name="commission_rate" type="number" step="0.01" min="0" max="100" value="0"></label>
  </div>
  <div class="row">
    <label>٪ سود خالص فروش (ویزیتور)<input name="profit_rate" type="number" step="0.01" min="0" max="100" value="0"></label>
    <label>اخطار سررسید چک (روز)<input name="check_alert_days" type="number" min="1" max="90" value="{{ $defaultAlert }}"></label>
  </div>
  <label>یادداشت<input name="notes"></label>
  <label class="check"><input type="checkbox" name="is_active" value="1" checked> فعال</label>
  <button class="btn" type="submit">ثبت کارمند / ویزیتور</button>
</form>
</div></div>
<div class="panel"><div class="hd"><strong>پرونده‌ها</strong></div><div class="bd" style="padding:0">
@forelse($rows as $r)
<form method="post" action="{{ url('/admin/accounting/staff/'.$r->id.'/update') }}" class="form" style="padding:.85rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>نام<input name="name" value="{{ $r->name }}"></label>
    <label>نوع
      <select name="kind">
        @foreach($kinds as $k=>$lab)<option value="{{ $k }}" @selected(($r->kind ?? 'employee')===$k)>{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>حقوق پایه<input name="base_salary" value="{{ $r->base_salary ?? 0 }}"></label>
    <label>٪ کمیسیون<input name="commission_rate" type="number" step="0.01" value="{{ $r->commission_rate ?? 0 }}"></label>
  </div>
  <div class="row">
    <label>٪ سود خالص<input name="profit_rate" type="number" step="0.01" value="{{ $r->profit_rate ?? 0 }}"></label>
    <label>اخطار چک (روز)<input name="check_alert_days" type="number" value="{{ $r->check_alert_days ?? $defaultAlert }}"></label>
  </div>
  <div class="row">
    <label>نقش<input name="role" value="{{ $r->role }}"></label>
    <label>تلفن<input name="phone" value="{{ $r->phone }}"></label>
  </div>
  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($r->is_active)> فعال</label>
  <div class="actions">
    <button class="btn" type="submit">ذخیره</button>
    <button class="btn g" type="submit" formaction="{{ url('/admin/accounting/staff/'.$r->id.'/delete') }}">غیرفعال</button>
  </div>
</form>
@empty
  <div style="padding:1rem">هنوز کارمند یا ویزیتوری تعریف نشده.</div>
@endforelse
</div></div>
@endsection
