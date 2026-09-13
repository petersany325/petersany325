@extends('layouts.app')

@section('title', 'کارتابل ارجاع نماینده | '.shop_name())
@section('page_title', 'کارتابل ارجاع نماینده')
@section('window_title', 'تأیید یا رد قبض ارسالی همکاران لایسنس‌دار')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>قبض شبکه:</strong>
    <p class="muted" style="margin:6px 0 0;">همکار مبدأ با قبض خودش ارجاع می‌دهد؛ اینجا قبض کامل مشتری را می‌بینید. منشی باید تأیید و ثبت کند یا رد کند تا قبض برگردد. بعد از تأیید، بقیه کار طبق روند داخلی همین مجموعه است.</p>
    @if(!empty($pull['message']))
        <p class="muted" style="margin:8px 0 0;">{{ $pull['message'] }}</p>
    @endif
</section>

<section class="panel handoff-toolbar">
    <form method="GET" class="accept-row accept-row-4" style="align-items:end;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="قبض / سریال / همکار / مشتری">
        </div>
        <div>
            <label>نمایش</label>
            <select name="tab">
                <option value="pending" @selected($tab === 'pending')>در انتظار تأیید منشی</option>
                <option value="inbound" @selected($tab === 'inbound')>ورودی تأییدشده</option>
                <option value="ready" @selected($tab === 'ready')>آماده برگشت به مبدأ</option>
                <option value="outbound" @selected($tab === 'outbound')>ارسال‌شده به همکار</option>
                <option value="all" @selected($tab === 'all')>همه</option>
            </select>
        </div>
        <div class="actions" style="grid-column:1/-1;margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">اعمال</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">فهرست همکاران شبکه</a>
            <a class="btn btn-ghost" href="{{ route('handoffs.index') }}">کارتابل ارجاع تعمیر</a>
        </div>
    </form>
    <form method="POST" action="{{ route('partners.pull') }}" style="margin-top:10px;">
        @csrf
        <button class="btn btn-secondary" type="submit">دریافت ارجاع‌های جدید از شبکه</button>
    </form>
    <div class="stats stats-compact" style="margin-top:10px;">
        <div class="stat"><div class="label">منتظر تأیید</div><div class="value">{{ number_format($stats['pending']) }}</div></div>
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
                <th>همکار</th>
                <th>قبض مبدأ</th>
                <th>مشتری</th>
                <th>دستگاه</th>
                <th>تأیید</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $r)
                <tr @if($r->partner_approval_status === 'pending') style="background:#fff8e8;" @endif>
                    <td>
                        <a href="{{ route('receptions.show', $r) }}">{{ $r->receipt_no ?: $r->ticket_no }}</a>
                        <div class="muted">{{ $r->ticket_no }}</div>
                    </td>
                    <td>
                        @if($r->partner_flow === 'outbound')
                            {{ $r->partnerReferredTo?->displayName() ?: '—' }}
                        @else
                            {{ $r->partner?->displayName() ?: '—' }}
                        @endif
                        <div class="muted" dir="ltr">{{ $r->partner_flow === 'outbound' ? ($r->partnerReferredTo?->domain) : ($r->partner?->domain) }}</div>
                    </td>
                    <td dir="ltr">{{ $r->partner_peer_receipt_no ?: '—' }}</td>
                    <td>
                        {{ $r->customer?->name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->customer?->phone }}</div>
                    </td>
                    <td>
                        {{ $r->product_name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->serial_number }}</div>
                    </td>
                    <td>{{ $r->partnerApprovalLabel() }}</td>
                    <td>{{ \App\Models\Reception::STATUSES[$r->status] ?? $r->status }}</td>
                    <td class="actions" style="flex-wrap:wrap;">
                        <a class="btn btn-ghost" href="{{ route('receptions.show', $r) }}">باز کردن</a>
                        @if($r->partner_approval_status === 'pending')
                            <form method="POST" action="{{ route('partners.approve', $r) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">تأیید و ثبت</button>
                            </form>
                            <form method="POST" action="{{ route('partners.reject', $r) }}" onsubmit="return confirm('قبض رد و به همکار مبدأ برگردد؟');">
                                @csrf
                                <input type="hidden" name="reason" value="رد توسط منشی">
                                <button class="btn btn-danger" type="submit">تأیید نشد / برگشت</button>
                            </form>
                        @endif
                        @if($r->partner_flow === 'inbound' && $r->partner_approval_status === 'approved' && in_array($r->status, ['ready', 'unrepairable'], true))
                            <form method="POST" action="{{ route('partners.mark-returned', $r) }}">
                                @csrf
                                <button class="btn btn-secondary" type="submit">آماده برگشت به مبدأ</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
