@extends('layouts.app')

@section('title', 'گزارش ارجاع شبکه | '.shop_name())
@section('page_title', 'گزارش ارجاع نمایندگان شبکه')
@section('window_title', 'قبض مبدأ، قبض این مجموعه، وضعیت تأیید و برگشت')

@section('content')
<section class="panel">
    <form method="GET" class="accept-row accept-row-4" style="align-items:end;">
        <div>
            <label>از تاریخ</label>
            <input type="date" name="from" value="{{ $from }}">
        </div>
        <div>
            <label>تا تاریخ</label>
            <input type="date" name="to" value="{{ $to }}">
        </div>
        <div>
            <label>جریان</label>
            <select name="flow">
                <option value="all" @selected($flow === 'all')>همه</option>
                <option value="inbound" @selected($flow === 'inbound')>ورودی</option>
                <option value="outbound" @selected($flow === 'outbound')>ارسال</option>
                <option value="returned" @selected($flow === 'returned')>برگشت</option>
            </select>
        </div>
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="قبض / سریال / همکار / مشتری">
        </div>
        <div class="actions" style="grid-column:1/-1;margin:0;">
            <button class="btn btn-primary" type="submit">اعمال</button>
            <a class="btn btn-ghost" href="{{ route('partners.report') }}">پاک</a>
            <a class="btn btn-secondary" href="{{ route('partners.cartable') }}">کارتابل</a>
        </div>
    </form>
    <div class="stats stats-compact" style="margin-top:10px;">
        <div class="stat"><div class="label">کل ردیف</div><div class="value">{{ number_format($stats['total']) }}</div></div>
        <div class="stat"><div class="label">منتظر تأیید</div><div class="value">{{ number_format($stats['pending']) }}</div></div>
        <div class="stat"><div class="label">تأییدشده</div><div class="value">{{ number_format($stats['approved']) }}</div></div>
        <div class="stat"><div class="label">ردشده</div><div class="value">{{ number_format($stats['rejected']) }}</div></div>
        <div class="stat"><div class="label">برگشتی</div><div class="value">{{ number_format($stats['returned']) }}</div></div>
    </div>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>تاریخ</th>
                <th>جریان</th>
                <th>قبض اینجا</th>
                <th>قبض نماینده</th>
                <th>همکار</th>
                <th>مشتری</th>
                <th>سریال</th>
                <th>تأیید شبکه</th>
                <th>وضعیت</th>
                <th>مبلغ</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($r->partner_flow === 'inbound') ورودی
                        @elseif($r->partner_flow === 'outbound') ارسال
                        @elseif($r->partner_flow === 'returned') برگشت
                        @else {{ $r->partner_flow }}
                        @endif
                    </td>
                    <td dir="ltr"><a href="{{ route('receptions.show', $r) }}">{{ $r->receipt_no }}</a></td>
                    <td dir="ltr">{{ $r->partner_peer_receipt_no ?: '—' }}</td>
                    <td>
                        @if($r->partner_flow === 'outbound')
                            {{ $r->partnerReferredTo?->displayName() ?: '—' }}
                        @else
                            {{ $r->partner?->displayName() ?: '—' }}
                        @endif
                    </td>
                    <td>{{ $r->customer?->name }} <span class="muted" dir="ltr">{{ $r->customer?->phone }}</span></td>
                    <td dir="ltr">{{ $r->serial_number ?: '—' }}</td>
                    <td>{{ $r->partnerApprovalLabel() }}</td>
                    <td>{{ \App\Models\Reception::STATUSES[$r->status] ?? $r->status }}</td>
                    <td>{{ number_format((float) ($r->total_amount ?: $r->estimated_cost ?: 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">در این بازه موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
