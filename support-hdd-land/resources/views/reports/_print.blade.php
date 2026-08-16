@php
    $printTitle = trim($printTitle ?? '');
    if ($printTitle === '') {
        $printTitle = trim($__env->yieldContent('page_title'));
    }
    if ($printTitle === '') {
        $printTitle = 'گزارش';
    }
    $printRange = $printRange ?? null;
    if ($printRange === null) {
        try {
            $rsPrint = \App\Support\ReportSettings::all();
            if (! empty($rsPrint['from']) && ! empty($rsPrint['to'])) {
                $printRange = jalali_date($rsPrint['from']).' تا '.jalali_date($rsPrint['to']);
            }
        } catch (\Throwable $e) {
            $printRange = null;
        }
    }
@endphp

@once
    @push('head')
        <style>
            .report-print-bar {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
                margin-bottom: 10px;
            }
            .report-print-header { display: none; }
            @media print {
                .report-print-header {
                    display: flex !important;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 12px;
                    margin-bottom: 12px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #111;
                }
                .report-print-brand { font-size: 16px; font-weight: 800; }
                .report-print-title { font-size: 14px; font-weight: 700; margin-top: 2px; }
                .report-print-meta { font-size: 11px; color: #333; text-align: left; direction: ltr; }
            }
        </style>
    @endpush
@endonce

<div class="report-print-bar no-print">
    <button class="btn btn-secondary" type="button" onclick="window.print()">چاپ گزارش</button>
</div>

<div class="report-print-header" aria-hidden="true">
    <div>
        <div class="report-print-brand">{{ shop_name() }}</div>
        <div class="report-print-title">{{ $printTitle }}</div>
        @if($printRange)
            <div class="muted" style="font-size:12px;margin-top:2px;">بازه: {{ $printRange }}</div>
        @endif
    </div>
    <div class="report-print-meta">{{ jalali_like(now()) }}</div>
</div>
