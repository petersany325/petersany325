@extends('accounting::layouts.acc')
@section('title','چک‌ها')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div>
    <h1>مدیریت چک‌ها</h1>
    <p>پرداختی، دریافتی، برگشتی، تحویل و وصول</p>
  </div>
  <div class="actions">
    <a class="btn g" href="{{ route('admin.accounting.reports.checks') }}">گزارش چک</a>
  </div>
</div>

@if($summary->isNotEmpty())
<div class="grid">
  @foreach($statuses as $k=>$lab)
    @if(isset($summary[$k]))
      <div class="card"><h3>{{ $lab }}</h3><div class="v">{{ $summary[$k]->c }}</div><div class="s">{{ $m($summary[$k]->s) }}</div></div>
    @endif
  @endforeach
</div>
@endif

<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>جهت
      <select name="direction">
        <option value="">همه</option>
        @foreach($directions as $k=>$lab)
          <option value="{{ $k }}" @selected($direction===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
    <label>وضعیت
      <select name="status">
        <option value="">همه</option>
        @foreach($statuses as $k=>$lab)
          <option value="{{ $k }}" @selected($status===$k)>{{ $lab }}</option>
        @endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>جستجو<input name="q" value="{{ $q }}" placeholder="شماره / صیاد / طرف"></label>
    <label style="align-self:end"><button class="btn" type="submit">فیلتر</button></label>
  </div>
</form>

<div class="panel"><div class="hd"><strong>ثبت چک جدید</strong></div><div class="bd">
<form method="post" action="{{ route('admin.accounting.checks.store') }}" class="form">@csrf
  <div class="row">
    <label>شماره چک<input name="number" placeholder="خالی = خودکار"></label>
    <label>جهت
      <select name="direction" required>
        @foreach($directions as $k=>$lab)<option value="{{ $k }}">{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>مبلغ (تومان)<input name="amount" required></label>
    <label>وضعیت
      <select name="status">
        @foreach($statuses as $k=>$lab)<option value="{{ $k }}">{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>طرف حساب<input name="party_name"></label>
    <label>شناسه کاربر<input name="party_user_id" type="number"></label>
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
    <label>شعبه<input name="branch"></label>
    <label>شماره حساب<input name="account_no"></label>
  </div>
  <div class="row">
    <label>صیاد<input name="sayad"></label>
    <label>تاریخ صدور<input type="date" name="issue_date" value="{{ now()->toDateString() }}"></label>
  </div>
  <div class="row">
    <label>سررسید<input type="date" name="due_date"></label>
    <label>یادداشت<input name="notes"></label>
  </div>
  <button class="btn" type="submit">ثبت چک</button>
</form>
</div></div>

<div class="panel"><div class="hd"><strong>لیست چک‌ها</strong></div><div class="bd" style="padding:0">
@forelse($items as $c)
<form method="post" action="{{ route('admin.accounting.checks.update', $c->id) }}" class="form" style="padding:.85rem;border-bottom:1px solid var(--line)">
  @csrf
  <div class="row">
    <label>شماره<input name="number" value="{{ $c->number }}"></label>
    <label>جهت
      <select name="direction">
        @foreach($directions as $k=>$lab)<option value="{{ $k }}" @selected($c->direction===$k)>{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>مبلغ<input name="amount" value="{{ $c->amount }}"></label>
    <label>وضعیت
      <select name="status">
        @foreach($statuses as $k=>$lab)<option value="{{ $k }}" @selected($c->status===$k)>{{ $lab }}</option>@endforeach
      </select>
    </label>
  </div>
  <div class="row">
    <label>طرف<input name="party_name" value="{{ $c->party_name }}"></label>
    <label>بانک<input name="bank_name" value="{{ $c->bank_name }}"></label>
  </div>
  <div class="row">
    <label>صیاد<input name="sayad" value="{{ $c->sayad }}"></label>
    <label>سررسید<input type="date" name="due_date" value="{{ $c->due_date }}"></label>
  </div>
  <div class="row">
    <label>صدور<input type="date" name="issue_date" value="{{ $c->issue_date }}"></label>
    <label>یادداشت<input name="notes" value="{{ $c->notes }}"></label>
  </div>
  <div class="actions">
    <button class="btn" type="submit">ذخیره</button>
    @foreach(['received'=>'وصول','paid'=>'پرداخت','returned'=>'برگشت','delivered'=>'تحویل','bounced'=>'برگشت‌خورده'] as $st=>$lab)
      <button class="btn g" type="submit" formaction="{{ route('admin.accounting.checks.status', $c->id) }}" name="status" value="{{ $st }}">{{ $lab }}</button>
    @endforeach
    <button class="btn g" type="submit" formaction="{{ route('admin.accounting.checks.delete', $c->id) }}" onclick="return confirm('حذف شود؟')">حذف</button>
  </div>
</form>
@empty
  <div style="padding:1rem">چکی ثبت نشده است.</div>
@endforelse
</div></div>
@endsection
