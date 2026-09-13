@extends('layouts.app')

@section('title', 'کارتابل ارجاع نماینده | '.shop_name())
@section('page_title', 'کارتابل ارجاع نماینده')
@section('window_title', 'قبض‌های همکار — طرف حساب نماینده است')

@section('content')
<section class="panel handoff-toolbar">
    <form method="GET" class="accept-row accept-row-4" style="align-items:end;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="قبض / سریال / نماینده / قبض مبدأ">
        </div>
        <div>
            <label>نمایش</label>
            <select name="tab">
                <option value="inbound" @selected($tab === 'inbound')>ورودی از نماینده</option>
                <option value="ready" @selected($tab === 'ready')>آماده برگشت به نماینده</option>
                <option value="outbound" @selected($tab === 'outbound')>ارسال‌شده به نماینده</option>
                <option value="all" @selected($tab === 'all')>همه</option>
            </select>
        </div>
        <div class="actions" style="grid-column:1/-1;margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">اعمال</button>
            <a class="btn btn-secondary" href="{{ route('partners.intake') }}">پذیرش از نماینده</a>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">فهرست نمایندگان</a>
            <a class="btn btn-ghost" href="{{ route('handoffs.index') }}">کارتابل ارجاع تعمیر</a>
        </div>
    </form>
    <div class="stats stats-compact" style="margin-top:10px;">
        <div class="stat"><div class="label">ورودی فعال</div><div class="value">{{ number_format($stats['inbound']) }}</div></div>
        <div class="stat"><div class="label">آماده برگشت</div><div class="value">{{ number_format($stats['ready']) }}</div></div>
        <div class="stat"><div class="label">ارسال‌شده</div><div class="value">{{ number_format($stats['outbound']) }}</div></div>
    </div>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض اینجا</th>
                <th>نماینده</th>
                <th>قبض مبدأ</th>
                <th>دستگاه</th>
                <th>وضعیت</th>
                <th>جریان</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $r)
                <tr>
                    <td>
                        <a href="{{ route('receptions.show', $r) }}">{{ $r->receipt_no ?: $r->ticket_no }}</a>
                        <div class="muted">{{ $r->ticket_no }}</div>
                    </td>
                    <td>
                        @if($r->partner_flow === 'outbound')
                            {{ $r->partnerReferredTo?->displayName() ?: '—' }}
                        @else
                            {{ $r->partner?->displayName() ?: $r->customer?->name }}
                        @endif
                    </td>
                    <td dir="ltr">{{ $r->partner_peer_receipt_no ?: '—' }}</td>
                    <td>
                        {{ $r->product_name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->serial_number }}</div>
                        @if($r->partner_end_customer_note)
                            <div class="muted">یادداشت مشتری: {{ $r->partner_end_customer_note }}</div>
                        @endif
                    </td>
                    <td>{{ \App\Models\Reception::STATUSES[$r->status] ?? $r->status }}</td>
                    <td>
                        @if($r->partner_flow === 'inbound') ورودی
                        @elseif($r->partner_flow === 'outbound') ارسال
                        @elseif($r->partner_flow === 'returned') برگشت
                        @else —
                        @endif
                    </td>
                    <td class="actions">
                        <a class="btn btn-ghost" href="{{ route('receptions.show', $r) }}">باز کردن</a>
                        @if($r->partner_flow === 'inbound' && in_array($r->status, ['ready', 'unrepairable'], true))
                            <form method="POST" action="{{ route('partners.mark-returned', $r) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">آماده برگشت به نماینده</button>
                            </form>
                        @endif
                        @if($r->partner_flow === 'outbound')
                            <form method="POST" action="{{ route('partners.mark-returned', $r) }}">
                                @csrf
                                <button class="btn btn-secondary" type="submit">برگشت از نماینده</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
