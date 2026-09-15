@extends('accounting::layouts.account')
@section('title', 'اقساط من')
@section('content')
<header>
  <h1>درخواست‌های اقساط</h1>
  <a href="{{ route('account.installments.create') }}">درخواست جدید</a>
</header>
@if(session('success'))<div class="card" style="border-color:#1f7a4c;color:#1f7a4c">{{ session('success') }}</div>@endif
@if(session('error'))<div class="card" style="border-color:#b42318;color:#b42318">{{ session('error') }}</div>@endif
@forelse($items as $r)
  <a class="card" href="{{ route('account.installments.show', $r->id) }}" style="display:block;color:inherit;text-decoration:none">
    <div class="row">
      <div>
        <strong>{{ $r->number }}</strong>
        <div class="meta">{{ $r->product_title }} · {{ $r->months }} قسط</div>
      </div>
      <div style="text-align:left">
        <div class="price">{{ number_format((int)$r->monthly_amount) }}</div>
        <span class="badge">{{ $statuses[$r->status] ?? $r->status }}</span>
      </div>
    </div>
  </a>
@empty
  <div class="card empty">هنوز درخواست اقساطی ندارید.</div>
@endforelse
<p style="text-align:center;margin-top:1rem">
  <a href="{{ url('/account/tickets') }}">پیگیری از تیکت پشتیبانی</a>
</p>
@endsection
