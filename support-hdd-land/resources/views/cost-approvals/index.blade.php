@extends('layouts.app')
@section('title', 'تأیید هزینه / جراحی | '.shop_name())
@section('page_title', 'کارتابل تأیید هزینه')
@section('window_title', 'جراحی، بازیابی و خدمات مشمول تأیید مشتری')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2 style="margin:0;">تأیید هزینه / جراحی</h2>
            <p class="lead" style="margin:4px 0 0;">فقط برای خدماتی که در تنظیمات مشخص شده‌اند (مثل جراحی و بازیابی)</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-secondary" href="{{ route('cost-approvals.settings') }}">تنظیم خدمات مشمول</a>
        </div>
    </div>

    <div class="emp-stat-row" style="margin-top:10px;">
        <div class="emp-stat tone-sms"><span>در انتظار</span><strong>{{ $stats['pending'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>تأییدشده</span><strong>{{ $stats['approved'] }}</strong></div>
        <div class="emp-stat"><span>ردشده</span><strong>{{ $stats['rejected'] }}</strong></div>
        <div class="emp-stat"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
    </div>

    <div class="muted" style="font-size:11.5px;margin:8px 0 12px;">
        خدمات فعال:
        {{ $enabledServices ? implode(' · ', $enabledServices) : 'هیچ خدمتی انتخاب نشده' }}
    </div>

    @if($needsApproval->count())
        <h3 style="font-size:12.5px;margin:12px 0 6px;">قبض‌های نیازمند تأیید (هنوز تأیید نهایی نشده)</h3>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead>
                    <tr>
                        <th>قبض</th>
                        <th>مشتری</th>
                        <th>خدمت</th>
                        <th>مبلغ</th>
                        <th>وضعیت تأیید</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($needsApproval as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}<div class="muted" dir="ltr">{{ $r->customer?->phone }}</div></td>
                        <td>{{ $r->service_type ?: '—' }} / {{ $r->repair_type ?: '—' }}</td>
                        <td>{{ number_format((int) ($r->total_amount ?: $r->estimated_cost)) }}</td>
                        <td>{{ $statusLabels[$r->cost_approval_status] ?? ($r->cost_approval_status ?: 'ارسال نشده') }}</td>
                        <td>
                            <form method="POST" action="{{ route('cost-approvals.send', $r) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="send_sms" value="1">
                                <button class="btn btn-primary" type="submit">ارسال لینک</button>
                            </form>
                            <a class="btn btn-ghost" href="{{ route('receptions.show', $r) }}">قبض</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form class="ticket-search-bar" method="GET" style="margin:14px 0 8px;">
        <div class="field">
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="قبض، مشتری، کد تأیید، خدمت">
        </div>
        <div class="field">
            <label>وضعیت</label>
            <select name="status">
                <option value="">همه</option>
                @foreach($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-secondary" type="submit">فیلتر</button>
        </div>
    </form>

    <h3 style="font-size:12.5px;margin:8px 0;">تاریخچه لینک‌های تأیید</h3>
    <div class="table-wrap">
        <table class="data compact-table">
            <thead>
                <tr>
                    <th>نسخه</th>
                    <th>قبض</th>
                    <th>مشتری</th>
                    <th>خدمت</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>ارسال</th>
                    <th>مشاهده</th>
                    <th>تصمیم</th>
                    <th>کد</th>
                </tr>
            </thead>
            <tbody>
            @forelse($approvals as $ap)
                <tr>
                    <td>V{{ $ap->version }}</td>
                    <td>
                        @if($ap->reception_id)
                            <a href="{{ route('receptions.show', $ap->reception_id) }}">{{ $ap->reception?->ticket_no }}</a>
                        @else — @endif
                    </td>
                    <td>{{ $ap->customer?->name ?: '—' }}</td>
                    <td>{{ $ap->reception?->service_type ?: '—' }} / {{ $ap->reception?->repair_type ?: '—' }}</td>
                    <td>{{ number_format((int) $ap->amount) }}</td>
                    <td>{{ $ap->statusLabel() }}</td>
                    <td>{{ jalali_like($ap->sent_at) }}</td>
                    <td>{{ jalali_like($ap->viewed_at) }}</td>
                    <td>{{ jalali_like($ap->decided_at) }}</td>
                    <td dir="ltr">{{ $ap->approval_code ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10">موردی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $approvals->links('partials.pagination') }}
</div>
@endsection
