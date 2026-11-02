@extends('layouts.portal')
@section('title', $title.' | سرزمین هارد')

@section('content')
<header class="p-top compact"><meta charset="utf-8">
    <a class="p-back" href="{{ route('portal.home') }}">→</a>
    <div>
        <div class="p-hello">{{ $title }}</div>
        <div class="p-sub">{{ $tickets->total() }} قبض</div>
    </div>
</header>

<div class="p-filter-row">
    <a class="{{ $status === '' ? 'is-on' : '' }}" href="{{ route('portal.tickets') }}">همه</a>
    <a class="{{ $status === 'repairing' ? 'is-on' : '' }}" href="{{ route('portal.tickets', ['status' => 'repairing']) }}">تعمیر</a>
    <a class="{{ $status === 'waiting_part' ? 'is-on' : '' }}" href="{{ route('portal.tickets', ['status' => 'waiting_part']) }}">قطعه</a>
    <a class="{{ $status === 'ready' ? 'is-on' : '' }}" href="{{ route('portal.tickets', ['status' => 'ready']) }}">آماده</a>
    <a class="{{ $status === 'delivered' ? 'is-on' : '' }}" href="{{ route('portal.tickets', ['status' => 'delivered']) }}">تحویل</a>
</div>

<div class="p-ticket-list pad">
    @forelse($tickets as $t)
        @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => $t->status === 'ready'])
    @empty
        <div class="p-empty">قبضی در این وضعیت نیست.</div>
    @endforelse
</div>

@if($tickets->hasPages())
    <div class="p-pager">{{ $tickets->links('partials.pagination') }}</div>
@endif

@if($status === 'ready' && count($payLinks))
<section class="p-section">
    <h2>درگاه‌های پرداخت</h2>
    @include('partials.payment-links', ['payLinks' => $payLinks, 'payTitle' => 'پرداخت آنلاین', 'compact' => true])
</section>
@endif
@endsection
