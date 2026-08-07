@extends('layouts.portal')
@section('title', 'جستجوی قبض | سرزمین هارد')

@section('content')
<header class="p-top compact"><meta charset="utf-8">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">جستجوی قبض</div>
        <div class="p-sub">شماره قبض، سریال، برند یا مدل</div>
    </div>
</header>

<form class="p-search-bar" method="GET" action="{{ route('portal.search') }}">
    <input type="search" name="q" value="{{ $q }}" placeholder="مثال: SH-100 یا سریال..." autofocus>
    <button type="submit" class="p-btn primary sm">جستجو</button>
</form>

<div class="p-ticket-list pad">
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
@endsection
