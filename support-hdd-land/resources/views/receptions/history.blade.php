<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تاریخچه {{ $reception->ticket_no }} | سرزمین هارد</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=erp11">
    <style>
        body { background:#eef1f5; margin:0; padding:16px; font-family:Vazirmatn,sans-serif; }
        .hist-wrap { max-width:920px; margin:0 auto; display:grid; gap:12px; }
        .hist-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        @media print {
            .hist-actions { display:none !important; }
            body { background:#fff; padding:0; }
        }
    </style>
</head>
<body>
<div class="hist-wrap">
    <div class="panel">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <h2 style="margin:0;">تاریخچه و گزارش قبض {{ $reception->ticket_no }}</h2>
                <p class="muted" style="margin:4px 0 0;">
                    {{ $reception->customer?->name }} —
                    {{ $reception->product_name }} {{ $reception->brand }} {{ $reception->model }}
                    @if($reception->serial_number)
                        · سریال <span dir="ltr">{{ $reception->serial_number }}</span>
                    @endif
                </p>
            </div>
            <div class="hist-actions">
                <span class="badge badge-{{ $reception->status }}">{{ $reception->statusLabel() }}</span>
                <button class="btn btn-secondary" type="button" onclick="window.print()">چاپ گزارش</button>
                <a class="btn btn-ghost" href="{{ route('receptions.show', $reception) }}">بازگشت به قبض</a>
            </div>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">خلاصه</h3>
        <div class="accept-row accept-row-4">
            <div><span class="muted">وضعیت</span><div>{{ $reception->statusLabel() }}</div></div>
            <div><span class="muted">محل دستگاه</span><div>{{ $reception->custodyLabel() }}</div></div>
            <div><span class="muted">ظرفیت هارد</span><div>{{ $reception->capacityLabel() }}</div></div>
            <div><span class="muted">تعمیرکار</span><div>{{ $reception->technician?->name ?: '—' }}</div></div>
            <div><span class="muted">جمع فاکتور</span><div>{{ toman($reception->total_amount) }}</div></div>
            <div><span class="muted">پرداخت‌شده</span><div>{{ toman($reception->paid_amount) }}</div></div>
            <div><span class="muted">مانده</span><div>{{ toman($reception->remainingAmount()) }}</div></div>
            <div><span class="muted">وضعیت تسویه</span><div>{{ $reception->financeStatusLabel() }}</div></div>
            <div><span class="muted">نحوه تحویل</span><div>{{ \App\Services\ReceptionSettlementService::MODES[$reception->settlement_mode] ?? ($reception->settlement_mode ?: '—') }}</div></div>
            <div><span class="muted">تحویل</span><div>{{ $reception->delivered_at ? jalali_like($reception->delivered_at) : '—' }}</div></div>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">تاریخچه وضعیت دستگاه</h3>
        @forelse(($statusLogs ?? collect()) as $log)
            <div class="rx-timeline-item">
                <div class="rx-timeline-dot"></div>
                <div>
                    <strong>{{ $log->displayTitle() }}</strong>
                    <div class="muted" style="font-size:11px;">
                        {{ jalali_like($log->created_at) }}
                        @if($log->actor) · {{ $log->actor->name }} @endif
                        @if($log->fromStatusLabel()) · از {{ $log->fromStatusLabel() }} @endif
                        → {{ $log->toStatusLabel() }}
                    </div>
                    @if($log->note)
                        <div style="font-size:12px;margin-top:2px;">{{ $log->note }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="muted" style="margin:0;">تاریخچه‌ای ثبت نشده است.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">گزارش‌های کار</h3>
        @forelse(($workReports ?? collect()) as $wr)
            <div style="padding:8px 0;border-bottom:1px dashed #e5e9ef;">
                <strong>{{ $wr->summary }}</strong>
                <div class="muted" style="font-size:11px;">
                    {{ jalali_like($wr->created_at) }} · {{ $wr->technician?->name ?: $wr->user?->name }}
                    @if($wr->needs_part) · نیاز به قطعه @endif
                </div>
                @if($wr->details)
                    <div style="font-size:12px;margin-top:2px;">{{ $wr->details }}</div>
                @endif
            </div>
        @empty
            <p class="muted" style="margin:0;">گزارش کاری نیست.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">ارجاع‌ها</h3>
        @if(($handoffs ?? collect())->count())
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>زمان</th><th>نوع</th><th>از</th><th>به</th><th>وضعیت</th></tr></thead>
                    <tbody>
                    @foreach($handoffs as $h)
                        <tr>
                            <td>{{ jalali_like($h->created_at) }}</td>
                            <td>{{ $h->directionLabel() }}</td>
                            <td>{{ $h->fromUser?->name }}</td>
                            <td>{{ $h->toTechnician?->name ?: ($h->toUser?->name ?: 'پذیرش') }}</td>
                            <td>{{ $h->statusLabel() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="margin:0;">ارجاعی ثبت نشده.</p>
        @endif
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">قطعات مصرفی / خروج</h3>
        @if($reception->parts->count())
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>قطعه</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                    <tbody>
                    @foreach($reception->parts as $rp)
                        <tr>
                            <td>{{ $rp->part?->name ?: ($rp->part_name ?: '—') }}</td>
                            <td>{{ $rp->qty }}</td>
                            <td>{{ toman($rp->total_price) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted" style="margin:0;">قطعه‌ای ثبت نشده.</p>
        @endif
    </div>
</div>
</body>
</html>
