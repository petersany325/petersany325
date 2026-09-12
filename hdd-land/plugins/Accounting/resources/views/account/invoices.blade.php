@extends('accounting::layouts.account')
@section('title', 'فاکتورهای من')
@section('content')
<header>
  <h1>فاکتورها و پیش‌فاکتورها</h1>
  <a href="{{ url('/app/account') }}">بازگشت</a>
</header>
@forelse($docs as $d)
  <a class="card" href="{{ route('account.invoices.show', $d->id) }}" style="display:block;color:inherit;text-decoration:none">
    <div class="row">
      <div>
        <strong>{{ $d->number }}</strong>
        <div class="meta">{{ $types[$d->type] ?? $d->type }} · {{ $d->doc_date }}</div>
      </div>
      <div style="text-align:left">
        <div class="price">{{ number_format((int)$d->total) }}</div>
        <span class="badge">{{ $d->status }}</span>
      </div>
    </div>
  </a>
@empty
  <div class="card empty">هنوز فاکتوری برای شما ثبت نشده است.</div>
@endforelse
@endsection
