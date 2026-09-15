@extends('accounting::layouts.acc')
@section('title','اسناد مالی')
@section('content')
@php $m = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top">
  <div><h1>اسناد مالی</h1><p>خرید، فروش، پیش‌فاکتور، سند دستی و حرکات انبار</p></div>
  <a class="btn" href="{{ route('admin.accounting.docs.create',['type'=>$type ?: 'sale']) }}">سند جدید</a>
</div>
<form method="get" class="form" style="margin-bottom:1rem">
  <div class="row">
    <label>نوع
      <select name="type" onchange="this.form.submit()">
        <option value="">همه</option>
        @foreach($types as $k=>$v)
          <option value="{{ $k }}" @selected($type===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </label>
    <label>جستجو<input type="search" name="q" value="{{ $search }}" placeholder="شماره یا طرف حساب"></label>
  </div>
</form>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>شماره</th><th>تاریخ</th><th>نوع</th><th>طرف</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
  <tbody>
  @forelse($docs as $d)
    <tr>
      <td>{{ $d->number }}</td>
      <td>{{ $d->doc_date }}</td>
      <td>{{ $types[$d->type] ?? $d->type }}</td>
      <td>{{ $d->party_name ?: '—' }}</td>
      <td>{{ $m($d->total) }}</td>
      <td><span class="badge {{ $d->status }}">{{ $d->status }}</span></td>
      <td><a href="{{ route('admin.accounting.doc',$d->id) }}">باز</a></td>
    </tr>
  @empty
    <tr><td colspan="7">موردی نیست.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
{{ $docs->withQueryString()->links() }}
@endsection
