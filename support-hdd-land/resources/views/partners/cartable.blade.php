@extends('layouts.app')

@section('title', 'کارتابل ارجاع نماینده | '.shop_name())
@section('page_title', 'کارتابل ارجاع نماینده')
@section('window_title', 'منتظر قطعه → تأیید/رد → تعمیر → برگشت به همکار اول')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>منطق شبکه (کامل):</strong>
    <ol class="muted" style="margin:6px 0 0;padding-right:18px;line-height:1.8;">
        <li>اول قبض را سرچ/انتخاب کنید، بعد نماینده را سرچ و ارجاع بزنید.</li>
        <li>در مقصد، قبض تا ورود قطعه در «منتظر تأیید منشی» می‌ماند.</li>
        <li>بعد تأیید: <b>قبض اولیه</b> این مجموعه برای تعمیر + <b>قبض ثانویه</b> حسابداری برای نماینده مبدأ.</li>
        <li>تعمیر → SMS → هزینه → حسابداری/تسویه و آماده‌سازی خروج؛ <b>سپس</b> ارجاع برگشت به همکار اول.</li>
    </ol>
    @if(!empty($pull['message']))
        <p class="muted" style="margin:8px 0 0;">{{ $pull['message'] }}</p>
    @endif
    <div class="actions" style="margin-top:10px;flex-wrap:wrap;">
        <a class="btn btn-primary" href="{{ route('partners.send') }}">ارجاع قبض جدید (سرچ قبض → نماینده)</a>
    </div>
</section>

<section class="panel handoff-toolbar">
    <form method="GET" class="accept-row accept-row-4" style="align-items:end;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="قبض اینجا / قبض مبدأ / سریال / مشتری">
        </div>
        <div>
            <label>نمایش</label>
            <select name="tab">
                <option value="pending" @selected($tab === 'pending')>منتظر قطعه / تأیید منشی</option>
                <option value="inbound" @selected($tab === 'inbound')>ورودی تأییدشده (در تعمیر)</option>
                <option value="ready" @selected($tab === 'ready')>آماده برگشت / برگشتی‌ها</option>
                <option value="outbound" @selected($tab === 'outbound')>ارسال‌شده به همکار</option>
                <option value="all" @selected($tab === 'all')>همه</option>
            </select>
        </div>
        <div class="actions" style="grid-column:1/-1;margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">اعمال</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">فهرست همکاران</a>
            <a class="btn btn-ghost" href="{{ route('partners.report') }}">گزارش شبکه</a>
            <a class="btn btn-ghost" href="{{ route('handoffs.index') }}">کارتابل ارجاع تعمیر</a>
        </div>
    </form>
    <form method="POST" action="{{ route('partners.pull') }}" style="margin-top:10px;">
        @csrf
        <button class="btn btn-secondary" type="submit">دریافت ارجاع‌های جدید از شبکه</button>
    </form>
    <div class="stats stats-compact" style="margin-top:10px;">
        <div class="stat"><div class="label">منتظر تأیید</div><div class="value">{{ number_format($stats['pending']) }}</div></div>
        <div class="stat"><div class="label">در تعمیر</div><div class="value">{{ number_format($stats['inbound']) }}</div></div>
        <div class="stat"><div class="label">برگشت/آماده</div><div class="value">{{ number_format($stats['ready']) }}</div></div>
        <div class="stat"><div class="label">ارسال‌شده</div><div class="value">{{ number_format($stats['outbound']) }}</div></div>
    </div>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض اولیه این مجموعه</th>
                <th>قبض نماینده مبدأ</th>
                <th>همکار</th>
                <th>مشتری</th>
                <th>قطعه / دستگاه</th>
                <th>تأیید شبکه</th>
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
                        @if($r->partnerSecondaries && $r->partnerSecondaries->isNotEmpty())
                            <div class="muted" style="font-size:10px;">ثانویه: {{ $r->partnerSecondaries->pluck('receipt_no')->filter()->implode('، ') }}</div>
                        @endif
                    </td>
                    <td dir="ltr"><strong>{{ $r->partner_peer_receipt_no ?: '—' }}</strong></td>
                    <td>
                        @if($r->partner_flow === 'outbound')
                            {{ $r->partnerReferredTo?->displayName() ?: '—' }}
                        @else
                            {{ $r->partner?->displayName() ?: '—' }}
                        @endif
                        <div class="muted" dir="ltr">{{ $r->partner_flow === 'outbound' ? ($r->partnerReferredTo?->domain) : ($r->partner?->domain) }}</div>
                    </td>
                    <td>
                        {{ $r->customer?->name ?: '—' }}
                        <div class="muted" dir="ltr">{{ $r->customer?->phone }}</div>
                    </td>
                    <td>
                        {{ $r->product_name ?: '—' }}
                        <div class="muted">{{ trim(($r->brand.' '.$r->model)) }}</div>
                        <div class="muted" dir="ltr">{{ $r->serial_number }}</div>
                    </td>
                    <td>{{ $r->partnerApprovalLabel() }}</td>
                    <td>{{ \App\Models\Reception::STATUSES[$r->status] ?? $r->status }}</td>
                    <td class="actions" style="flex-wrap:wrap;">
                        <a class="btn btn-ghost" href="{{ route('receptions.show', $r) }}">باز کردن</a>
                        @if($r->partner_approval_status === 'pending')
                            <form method="POST" action="{{ route('partners.approve', $r) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">قطعه رسید — تأیید قبض</button>
                            </form>
                            <form method="POST" action="{{ route('partners.reject', $r) }}" onsubmit="return confirm('ناهماهنگی؟ قبض رد و به مبدأ برگردد؟');">
                                @csrf
                                <input type="hidden" name="reason" value="ناهماهنگی مشخصات/قطعه">
                                <button class="btn btn-danger" type="submit">رد قبض</button>
                            </form>
                        @endif
                        @if($r->partner_flow === 'inbound' && $r->partner_approval_status === 'approved')
                            @if($r->canReturnToPartner())
                                <form method="POST" action="{{ route('partners.mark-returned', $r) }}" onsubmit="return confirm('ارجاع برگشت به همکار اول ثبت شود؟');">
                                    @csrf
                                    <button class="btn btn-secondary" type="submit">ارجاع برگشت به همکار اول</button>
                                </form>
                            @else
                                <span class="muted" style="font-size:10.5px;max-width:180px;display:inline-block;">{{ $r->partnerReturnBlockReason() }}</span>
                            @endif
                        @endif
                        @if($r->partner_flow === 'outbound' || ($r->partner_flow === 'returned' && in_array($r->partner_approval_status, ['returned', 'rejected'], true)))
                            <form method="POST" action="{{ route('partners.mark-returned', $r) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit" @disabled($r->blocksCustomerExitForPartner())>
                                    آماده خروج مشتری
                                </button>
                            </form>
                            @if($r->blocksCustomerExitForPartner())
                                <span class="muted" style="font-size:10.5px;">تا برگشت از مقصد، خروج مشتری قفل است.</span>
                            @endif
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
