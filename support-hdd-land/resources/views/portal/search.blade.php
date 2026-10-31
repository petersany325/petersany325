@extends('layouts.portal')
@section('title', 'جستجوی قبض | '.shop_name())

@section('content')
<header class="p-top">
    <div class="p-brand-mini">
        <a class="p-icon-btn" href="{{ route('portal.home') }}" title="بازگشت" style="text-decoration:none;display:grid;place-items:center;">→</a>
        <div>
            <div class="p-hello">جستجوی قبض</div>
            <div class="p-sub">شماره قبض، سریال، برند یا مدل</div>
        </div>
    </div>
</header>

<div class="portal-shell" style="padding-top:10px;">
<form class="p-search-bar" method="GET" action="{{ route('portal.search') }}">
    <input type="search" name="q" value="{{ $q }}" placeholder="مثال: SH-100 یا سریال..." autofocus>
    <button type="submit" class="p-btn primary">جستجو</button>
</form>

<div class="p-ticket-list">
    @if($q === '')
        <div class="p-empty">عبارت جستجو را وارد کنید.</div>
    @else
        @forelse($tickets as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => $t->status === 'ready'])
        @empty
            <div class="p-empty">نتیجه‌ای برای «{{ $q }}» پیدا نشد.</div>
        @endforelse
    @endif
</div>
</div>
@endsection
