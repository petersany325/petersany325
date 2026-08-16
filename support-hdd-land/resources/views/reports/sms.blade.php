@extends('layouts.app')
@section('title', 'گزارش پیامک | '.shop_name())
@section('page_title', 'گزارش پیامک قبض‌ها')
@section('window_title', 'همه پیامک‌های سیستم — موفق / ناموفق / تأیید هزینه')

@section('content')
@include('reports._settings')

<div class="sms-report-shell">
    <div class="sms-report-hero panel">
        <div>
            <div class="wh-eyebrow">{{ shop_name() }} · کارتابل پیامک</div>
            <h2 style="margin:0;">گزارش پیامک قبض‌ها</h2>
            <p class="lead" style="margin:4px 0 0;">
                @if($reception)
                    فیلتر فعال روی قبض
                    <strong>{{ $reception->ticket_no }}</strong>
                    — {{ $reception->customer?->name ?: '—' }}
                    <a class="btn btn-ghost" style="margin-right:8px;" href="{{ route('receptions.show', $reception) }}">بازگشت به قبض</a>
                    <a class="btn btn-ghost" href="{{ route('reports.sms') }}">حذف فیلتر قبض</a>
                @else
                    تمام پیامک‌های ارسال‌شده به مشتری و همکار، به‌همراه تأیید هزینه
                @endif
            </p>
        </div>
    </div>

    <div class="panel" style="margin-bottom:12px;">
        <div class="stats stats-compact">
            <div class="stat"><div class="label">کل</div><div class="value">{{ number_format($summary['total']) }}</div></div>
            <div class="stat"><div class="label">موفق</div><div class="value">{{ number_format($summary['ok']) }}</div></div>
            <div class="stat"><div class="label">ناموفق</div><div class="value">{{ number_format($summary['fail']) }}</div></div>
            <div class="stat"><div class="label">به مشتری</div><div class="value">{{ number_format($summary['customer']) }}</div></div>
            <div class="stat"><div class="label">همکار</div><div class="value">{{ number_format($summary['coworker']) }}</div></div>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.sms') }}" class="panel sms-report-filters no-print">
        @if($receptionId)
            <input type="hidden" name="reception_id" value="{{ $receptionId }}">
        @endif
        <div style="flex:1 1 240px;min-width:200px;">
            <label>شماره قبض / جستجو</label>
            @include('partials.receipt-search-input', [
                'name' => 'q',
                'value' => $q,
                'placeholder' => '1000',
                'hint' => 'T-20N ثابت؛ ادامه کد یا موبایل/نام',
                'allowFree' => true,
            ])
        </div>
        <select name="ok">
            <option value="">نتیجه: همه</option>
            <option value="1" @selected($okFilter === '1')>فقط موفق</option>
            <option value="0" @selected($okFilter === '0')>فقط ناموفق</option>
        </select>
        <select name="audience">
            <option value="">مخاطب: همه</option>
            <option value="customer" @selected($audience === 'customer')>مشتری</option>
            <option value="coworker" @selected($audience === 'coworker')>همکار</option>
        </select>
        <select name="status_key">
            <option value="">کلید وضعیت: همه</option>
            @foreach($statusKeys as $sk)
                <option value="{{ $sk }}" @selected($statusKey === $sk)>{{ $sk }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
        <a class="btn btn-ghost" href="{{ route('reports.sms', array_filter(['reception_id' => $receptionId])) }}">پاک</a>
    </form>

    @if(\App\Support\ReportSettings::showCharts())
    <div class="report-charts-row" style="margin-bottom:12px;">
        @include('reports._chart', ['id'=>'chartSms','title'=>'موفق در برابر ناموفق','labels'=>$chartSmsLabels,'values'=>$chartSmsValues,'type'=>'doughnut'])
        @include('reports._chart', ['id'=>'chartSmsStatus','title'=>'به تفکیک وضعیت','labels'=>$byStatus->pluck('status_key')->map(fn($v)=>$v?:'—')->values()->all(),'values'=>$byStatus->pluck('total')->map(fn($v)=>(int)$v)->values()->all()])
    </div>
    @endif

    <div class="panel" style="margin-bottom:12px;">
        <header class="wh-panel-head">
            <h3 style="margin:0;">فهرست پیامک‌ها</h3>
            <span class="muted">مرتب‌شده از جدید به قدیم</span>
        </header>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>زمان</th>
                    <th>نتیجه</th>
                    <th>قبض</th>
                    <th>مخاطب</th>
                    <th>موبایل</th>
                    <th>نوع / وضعیت</th>
                    <th>متن</th>
                    <th>فرستنده</th>
                    <th>پاسخ پنل</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $log)
                    <tr>
                        <td>{{ jalali_like($log->created_at) }}</td>
                        <td>
                            <span class="{{ $log->ok ? 'pill pill-ok' : 'pill pill-off' }}">{{ $log->ok ? 'موفق' : 'ناموفق' }}</span>
                        </td>
                        <td>
                            @if($log->reception_id)
                                <a href="{{ route('receptions.show', $log->reception_id) }}">{{ $log->reception?->ticket_no }}</a>
                                <div class="muted" style="font-size:10px;">
                                    <a href="{{ route('reports.sms', ['reception_id' => $log->reception_id]) }}">فقط این قبض</a>
                                </div>
                            @else — @endif
                        </td>
                        <td>{{ $log->audience === 'coworker' ? 'همکار' : 'مشتری' }}
                            <div class="muted" style="font-size:10px;">{{ $log->customer?->name }}</div>
                        </td>
                        <td dir="ltr">{{ $log->phone }}</td>
                        <td>
                            @if($log->status_key === 'cost_approval')
                                لینک تأیید هزینه
                            @else
                                {{ $log->rule?->title ?: ($log->status_key ?: '—') }}
                            @endif
                        </td>
                        <td style="max-width:280px;">
                            <div style="white-space:pre-wrap;font-size:11px;line-height:1.5;">{{ \Illuminate\Support\Str::limit($log->message, 220) }}</div>
                        </td>
                        <td>{{ $log->sender?->name ?: '—' }}</td>
                        <td class="muted" style="font-size:11px;">{{ \Illuminate\Support\Str::limit($log->provider_message, 80) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">پیامکی با این فیلتر پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links('partials.pagination') }}
    </div>

    <div class="split-2">
        <div class="panel">
            <h3 style="margin-top:0;">به تفکیک وضعیت قبض</h3>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>کلید وضعیت</th><th>کل</th><th>موفق</th></tr></thead>
                    <tbody>
                    @forelse($byStatus as $row)
                        <tr>
                            <td>
                                <a href="{{ route('reports.sms', array_filter(['status_key' => $row->status_key, 'reception_id' => $receptionId])) }}">
                                    {{ $row->status_key ?: '—' }}
                                </a>
                            </td>
                            <td>{{ $row->total }}</td>
                            <td>{{ $row->ok_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">پیامکی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <h3 style="margin-top:0;">آخرین ناموفق‌ها</h3>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>زمان</th><th>موبایل</th><th>قبض</th><th>پاسخ پنل</th></tr></thead>
                    <tbody>
                    @forelse($fails as $f)
                        <tr>
                            <td>{{ jalali_like($f->created_at) }}</td>
                            <td dir="ltr">{{ $f->phone }}</td>
                            <td>
                                @if($f->reception_id)
                                    <a href="{{ route('receptions.show', $f->reception_id) }}">{{ $f->reception?->ticket_no }}</a>
                                @else — @endif
                            </td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($f->provider_message, 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">ناموفقی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:12px;">
        <h2 style="margin-top:0;">تأیید هزینه مشتری (لینک یک‌بارمصرف)</h2>
        <div class="stats stats-compact">
            <div class="stat"><div class="label">ارسال‌شده</div><div class="value">{{ number_format($approvalSummary['sent'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">مشاهده‌شده+</div><div class="value">{{ number_format($approvalSummary['viewed'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">تأییدشده</div><div class="value">{{ number_format($approvalSummary['approved'] ?? 0) }}</div></div>
            <div class="stat"><div class="label">ردشده</div><div class="value">{{ number_format($approvalSummary['rejected'] ?? 0) }}</div></div>
        </div>
        <div class="table-wrap" style="margin-top:10px;">
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>قبض</th>
                        <th>مشتری</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>ارسال</th>
                        <th>مشاهده</th>
                        <th>تصمیم</th>
                        <th>کد</th>
                    </tr>
                </thead>
                <tbody>
                @forelse(($approvals ?? []) as $ap)
                    <tr>
                        <td>
                            @if($ap->reception_id)
                                <a href="{{ route('receptions.show', $ap->reception_id) }}">{{ $ap->reception?->ticket_no }}</a>
                            @else — @endif
                        </td>
                        <td>{{ $ap->customer?->name ?: '—' }}</td>
                        <td>{{ number_format((int) $ap->amount) }}</td>
                        <td>{{ $ap->statusLabel() }}</td>
                        <td>{{ jalali_like($ap->sent_at) }}</td>
                        <td>{{ jalali_like($ap->viewed_at) }}</td>
                        <td>{{ jalali_like($ap->decided_at) }}</td>
                        <td dir="ltr">{{ $ap->approval_code ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">در این بازه لینک تأییدی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@include('reports._charts-boot')
