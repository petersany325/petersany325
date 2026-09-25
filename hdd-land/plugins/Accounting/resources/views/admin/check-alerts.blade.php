@extends('accounting::layouts.acc')
@section('title','اخطار سررسید چک')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>اخطار روز مانده تا سررسید</h1>
    <p>پیش‌فرض {{ $defaultDays }} روز — برای هر مشتری یا شرکت قابل تعریف است. وقتی به پنجره اخطار برسد اینجا دیده می‌شود.</p>
  </div>
  <a class="btn g" href="{{ url('/admin/accounting/checkbooks') }}">دسته چک</a>
</div>
<div class="panel"><div class="hd"><strong>تنظیم پنجره اخطار</strong></div><div class="bd">
<form method="post" action="{{ url('/admin/accounting/checks/alerts') }}" class="form">@csrf
  <div class="row">
    <label>پیش‌فرض سیستم (روز)<input name="check_alert_days" type="number" min="1" max="90" value="{{ $defaultDays }}"></label>
    <label>نام مشتری / شرکت<input name="party_name" placeholder="برای تنظیم اختصاصی"></label>
  </div>
  <div class="row">
    <label>شناسه کاربر فروشگاه<input name="party_user_id" type="number"></label>
    <label>اخطار این طرف (روز)<input name="party_alert_days" type="number" min="1" max="90" value="{{ $defaultDays }}"></label>
  </div>
  <div class="row">
    <label>نوع طرف
      <select name="owner_type"><option value="person">شخص / مشتری</option><option value="company">شرکت</option></select>
    </label>
    <label style="align-self:end"><button class="btn" type="submit">ذخیره اخطار</button></label>
  </div>
</form>
@if($parties->isNotEmpty())
  <p style="color:var(--muted);font-size:.85rem;margin:.6rem 0 0">اختصاصی‌ها:
    @foreach($parties as $p) {{ $p->party_name }} ({{ $p->alert_days }} روز)@if(!$loop->last)، @endif @endforeach
  </p>
@endif
</div></div>
<div class="panel"><div class="hd"><strong>چک‌های نزدیک به سررسید</strong></div><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>طرف</th><th>مبلغ</th><th>سررسید</th><th>مانده</th><th>پنجره</th><th>وضعیت</th></tr></thead>
  <tbody>
  @forelse($items as $c)
    <tr>
      <td>{{ $c->number }}</td>
      <td>{{ $c->party_name ?: '—' }}</td>
      <td>{{ $m($c->amount) }}</td>
      <td>{{ $c->due_date }}</td>
      <td><span class="badge warn">{{ $c->days_left }} روز</span></td>
      <td>{{ $c->alert_window }} روز</td>
      <td>{{ $statuses[$c->status] ?? $c->status }}</td>
    </tr>
  @empty
    <tr><td colspan="7">چکی در پنجره اخطار نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
