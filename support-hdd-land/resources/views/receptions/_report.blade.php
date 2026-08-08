@php
    /** @var \App\Models\Reception $reception */
@endphp
<div class="panel report-card">
    <div class="report-head">
        <div>
            <h2>{{ $reception->ticket_no }}</h2>
            <div class="muted">
                قبض {{ $reception->receipt_no ?: '—' }}
                @if($reception->batch_code)
                    | گروه {{ $reception->batch_code }}
                @endif
                |
                پذیرش {{ jalali_like($reception->received_at) }} |
                {{ $reception->admission_type ?: '—' }}
            </div>
        </div>
        <div class="report-head-actions">
            <span class="badge badge-{{ $reception->status }}">{{ $reception->statusLabel() }}</span>
            <a class="btn btn-secondary" href="{{ route('receptions.show', $reception) }}">مدیریت قبض</a>
            <a class="btn btn-ghost" href="{{ route('receptions.print', $reception) }}" target="_blank">چاپ</a>
        </div>
    </div>

    <div class="report-section">
        <h3>مشتری</h3>
        <div class="accept-row accept-row-4">
            <div><span class="muted">نام</span><div>{{ $reception->customer?->name ?: '—' }}</div></div>
            <div><span class="muted">موبایل</span><div>{{ $reception->customer?->phone ?: '—' }}</div></div>
            <div><span class="muted">کد ملی</span><div>{{ $reception->customer?->national_code ?: '—' }}</div></div>
            <div><span class="muted">نحوه آشنایی</span><div>{{ $reception->customer?->referralSource?->name ?: '—' }}</div></div>
            <div style="grid-column:1/-1"><span class="muted">آدرس</span><div>{{ $reception->customer?->address ?: '—' }}</div></div>
        </div>
    </div>

    <div class="report-section">
        <h3>دستگاه و پذیرش</h3>
        <div class="accept-row accept-row-4">
            <div><span class="muted">کالا</span><div>{{ $reception->product_name }}</div></div>
            <div><span class="muted">برند / مدل</span><div>{{ trim(($reception->brand.' '.$reception->model)) ?: '—' }}</div></div>
            <div><span class="muted">سریال</span><div>{{ $reception->serial_number ?: '—' }}</div></div>
            <div><span class="muted">ظرفیت</span><div>{{ $reception->hdd_capacity ?: '—' }}</div></div>
            <div><span class="muted">خدمات</span><div>{{ $reception->service_type ?: '—' }}</div></div>
            <div><span class="muted">تعمیر</span><div>{{ $reception->repair_type ?: '—' }}</div></div>
            <div><span class="muted">تعمیرکار</span><div>{{ $reception->technician?->name ?: '—' }}</div></div>
            <div><span class="muted">گارانتی</span><div>{{ $reception->warranty_type ?: '—' }}{{ $reception->warranty_return ? ' (برگشت گارانتی)' : '' }}</div></div>
            <div><span class="muted">تحویل‌دهنده</span><div>{{ $reception->delivered_by ?: '—' }}</div></div>
            <div><span class="muted">معرف</span><div>{{ $reception->referrer ?: '—' }}</div></div>
            <div><span class="muted">ثبت‌کننده</span><div>{{ $reception->creator?->name ?: '—' }}</div></div>
            <div><span class="muted">تحویل</span><div>{{ $reception->delivered_at ? jalali_like($reception->delivered_at) : '—' }}</div></div>
        </div>
        <div class="accept-texts" style="margin-top:6px;">
            <div><span class="muted">عیب اظهار مشتری</span><div>{{ $reception->reported_fault ?: '—' }}</div></div>
            <div><span class="muted">لوازم همراه</span><div>{{ $reception->accessories ?: '—' }}</div></div>
            <div><span class="muted">وضعیت ظاهری</span><div>{{ $reception->appearance_notes ?: '—' }}</div></div>
        </div>
        @if($reception->final_fault || $reception->technician_notes || $reception->faultType)
            <div class="accept-row accept-row-3" style="margin-top:6px;">
                <div><span class="muted">نوع ایراد</span><div>{{ $reception->faultType?->name ?: '—' }}</div></div>
                <div style="grid-column:span 2"><span class="muted">ایراد نهایی / شرح کار</span><div>{{ $reception->final_fault ?: '—' }}</div></div>
                <div style="grid-column:1/-1"><span class="muted">یادداشت تعمیرکار</span><div>{{ $reception->technician_notes ?: '—' }}</div></div>
            </div>
        @endif
        @if($reception->photo_path)
            <div style="margin-top:6px;">
                <span class="muted">عکس دستگاه</span>
                <img src="{{ asset('storage/'.$reception->photo_path) }}" alt="photo" style="max-width:240px;margin-top:4px;border:1px solid #808080;">
            </div>
        @endif
    </div>

    <div class="report-section">
        <h3>خلاصه مالی</h3>
        <div class="accept-row accept-row-4">
            <div><span class="muted">بیعانه</span><div>{{ toman($reception->deposit) }}</div></div>
            <div><span class="muted">کارت‌خوان</span><div>{{ toman($reception->pos_amount) }}</div></div>
            <div><span class="muted">هزینه پذیرش</span><div>{{ toman($reception->admission_fee) }}</div></div>
            <div><span class="muted">تخمینی</span><div>{{ toman($reception->estimated_cost) }}</div></div>
            <div><span class="muted">اجرت</span><div>{{ toman($reception->labor_cost) }}</div></div>
            <div><span class="muted">قطعات</span><div>{{ toman($reception->parts_cost) }}</div></div>
            <div><span class="muted">مراحل هزینه</span><div>{{ toman($reception->stages_cost ?? 0) }}</div></div>
            <div><span class="muted">تخفیف</span><div>{{ toman($reception->discount) }}@if($reception->discount_reason)<small class="muted"> — {{ $reception->discount_reason }}</small>@endif</div></div>
            <div><span class="muted">پورسانت</span><div>{{ toman($reception->commission) }}</div></div>
            <div><span class="muted">جمع کل</span><div class="report-money">{{ toman($reception->total_amount) }}</div></div>
            <div><span class="muted">پرداخت‌شده</span><div>{{ toman($reception->paid_amount) }}</div></div>
            <div><span class="muted">مانده</span><div class="report-remain">{{ toman($reception->remainingAmount()) }}</div></div>
            <div><span class="muted">وضعیت تسویه</span><div>{{ $reception->financeStatusLabel() }}</div></div>
            <div><span class="muted">روش پرداخت</span><div>{{ $reception->payment_method ?: '—' }}</div></div>
            @if($reception->delivery_cancel_count)
                <div><span class="muted">لغو تحویل</span><div>{{ $reception->delivery_cancel_count }} بار</div></div>
            @endif
        </div>
    </div>

    @if(($reception->costStages ?? collect())->count())
    <div class="report-section">
        <h3>مراحل هزینه</h3>
        <div class="table-wrap">
            <table style="min-width:0;">
                <thead><tr><th>مرحله</th><th>مبلغ</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
                <tbody>
                @foreach($reception->costStages as $stage)
                    <tr>
                        <td>{{ $stage->stage_label }}</td>
                        <td>{{ toman($stage->amount) }}</td>
                        <td>{{ $stage->statusLabel() }}</td>
                        <td>{{ $stage->note ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(($reception->statusLogs ?? collect())->count())
    <div class="report-section">
        <h3>تاریخچه وضعیت</h3>
        <div class="rx-timeline">
            @foreach($reception->statusLogs->take(20) as $log)
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
            @endforeach
        </div>
    </div>
    @endif

    <div class="report-section split-2" style="margin-bottom:0;">
        <div>
            <h3>قطعات خرج‌شده</h3>
            <div class="table-wrap">
                <table style="min-width:0;">
                    <thead><tr><th>قطعه</th><th>تعداد</th><th>فی</th><th>جمع</th></tr></thead>
                    <tbody>
                    @forelse($reception->parts as $part)
                        <tr>
                            <td>{{ $part->part_name }}</td>
                            <td>{{ $part->quantity }}</td>
                            <td>{{ toman($part->unit_price) }}</td>
                            <td>{{ toman($part->total_price) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">قطعه‌ای ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>
            <h3>پرداخت‌ها</h3>
            <div class="table-wrap">
                <table style="min-width:0;">
                    <thead><tr><th>نوع</th><th>مبلغ</th><th>زمان</th><th>گیرنده</th></tr></thead>
                    <tbody>
                    @forelse($reception->payments as $payment)
                        <tr>
                            <td>{{ $payment->typeLabel() }} / {{ $payment->methodLabel() }}</td>
                            <td>{{ toman($payment->amount) }}</td>
                            <td>{{ jalali_like($payment->paid_at) }}</td>
                            <td>{{ $payment->receiver?->name ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">پرداختی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
