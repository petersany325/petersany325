@extends('layouts.app')

@section('title', 'جستجوی قبض | سرزمین هارد')
@section('page_title', 'جستجوی قبض')
@section('window_title', 'جستجوی قبض')

@section('content')
<form method="GET" action="{{ route('receptions.search') }}" class="ticket-search-bar" id="ticket-search-form">
    <div class="field">
        <label>عبارت جستجو</label>
        <input type="text"
               name="q"
               value="{{ $q }}"
               placeholder="نام، موبایل، شماره قبض، سریال…"
               autofocus
               required
               data-barcode
               data-ascii-en
               autocomplete="off">
    </div>
    <div class="field-sm">
        <label>وضعیت</label>
        <select name="status">
            <option value="">همه</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="actions" style="margin:0;">
        <button class="btn btn-primary" type="submit">جستجو</button>
        <a class="btn btn-ghost" href="{{ route('receptions.search') }}">پاک</a>
    </div>
</form>

@if($searched)
    <div style="margin:8px 0 4px;font-size:11.5px;">
        <strong>{{ $receptions->count() }}</strong>
        <span class="muted">نتیجه برای «{{ $q }}»</span>
        @if($receptions->count() >= 50)
            <span class="muted">— حداکثر ۵۰ مورد</span>
        @endif
    </div>

    <div class="search-hit-list">
        @forelse($receptions as $reception)
            <button type="button"
                    class="search-hit"
                    data-open-modal="#report-modal-{{ $reception->id }}">
                <span class="search-hit-main">
                    <strong>{{ $reception->customer?->name ?: 'بدون نام' }}</strong>
                    <span class="meta">
                        قبض {{ $reception->receipt_no ?: '—' }}
                        · {{ $reception->ticket_no }}
                        · {{ $reception->product_name ?: 'دستگاه' }}
                        @if($reception->serial_number) · <span dir="ltr">{{ $reception->serial_number }}</span>@endif
                    </span>
                </span>
                <span class="search-hit-side">
                    <span class="badge badge-{{ $reception->status }}">{{ $reception->statusLabel() }}</span>
                    <span class="muted">جزئیات ←</span>
                </span>
            </button>
        @empty
            <div class="panel">
                <p class="muted" style="margin:0;">قبضی پیدا نشد.</p>
            </div>
        @endforelse
    </div>

    @foreach($receptions as $reception)
        <div class="app-modal" id="report-modal-{{ $reception->id }}" hidden>
            <div class="app-modal-dialog" role="dialog" aria-modal="true">
                <div class="app-modal-head">
                    <strong>{{ $reception->customer?->name ?: 'قبض' }} — {{ $reception->ticket_no }}</strong>
                    <button type="button" class="app-modal-close" data-close-modal aria-label="بستن">×</button>
                </div>
                <div class="app-modal-body">
                    @include('receptions._report', ['reception' => $reception])
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
