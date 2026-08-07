@extends('layouts.app')
@section('title', 'حسابداری | سرزمین هارد')
@section('page_title', 'حسابداری تعمیرگاه')
@section('window_title', 'سیستم حسابداری')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'میز حسابداری',
    'accSub' => 'نمای زنده خزانه، درآمد و اسناد دوره',
    'accShowPeriod' => true,
])

<div class="acc-desk">
    <div class="acc-kpi-grid">
        <div class="acc-kpi tone-teal">
            <span class="acc-kpi-label">خزانه دوره</span>
            <strong class="acc-kpi-value">{{ number_format($treasury) }}</strong>
            <span class="acc-kpi-foot">صندوق {{ number_format($cash) }} · کارت {{ number_format($card) }}</span>
        </div>
        <div class="acc-kpi tone-blue">
            <span class="acc-kpi-label">درآمد دوره</span>
            <strong class="acc-kpi-value">{{ number_format($incomeTotal) }}</strong>
            <span class="acc-kpi-foot">خدمات + قطعات + پذیرش</span>
        </div>
        <div class="acc-kpi tone-rose">
            <span class="acc-kpi-label">بدهکاران</span>
            <strong class="acc-kpi-value">{{ number_format($receivable) }}</strong>
            <span class="acc-kpi-foot">مانده حساب مشتریان</span>
        </div>
        <div class="acc-kpi tone-amber">
            <span class="acc-kpi-label">سود ناخالص تقریبی</span>
            <strong class="acc-kpi-value">{{ number_format($gross) }}</strong>
            <span class="acc-kpi-foot">درآمد − بهای قطعات {{ number_format($cogs) }}</span>
        </div>
    </div>

    <div class="acc-mini-grid">
        <div class="acc-mini"><span>کارت‌به‌کارت</span><strong>{{ number_format($transfer) }}</strong></div>
        <div class="acc-mini"><span>اسناد دوره</span><strong>{{ $entryCount }}</strong></div>
        <div class="acc-mini"><span>تحویل‌شده</span><strong>{{ $deliveredCount }}</strong></div>
        <div class="acc-mini"><span>اجرت تحویل</span><strong>{{ number_format($laborTotal) }}</strong></div>
    </div>

    <div class="acc-panels">
        <section class="acc-panel">
            <header class="acc-panel-head"><meta charset="utf-8">
                <h3>آخرین اسناد</h3>
                <a href="{{ route('accounting.journals') }}">همه اسناد</a>
            </header>
            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead><tr><th>شماره</th><th>تاریخ</th><th>شرح</th><th>مبلغ</th></tr></thead>
                    <tbody>
                    @forelse($recentEntries as $e)
                        <tr>
                            <td><a class="acc-link" href="{{ route('accounting.show', $e) }}">{{ $e->entry_no }}</a></td>
                            <td>{{ jalali_date($e->entry_date?) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($e->description, 42) }}</td>
                            <td class="acc-num">{{ number_format($e->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">سندی نیست — بازسازی یا ثبت پرداخت کنید.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="acc-panel">
            <header class="acc-panel-head">
                <h3>پرداخت‌های دوره</h3>
                <a href="{{ route('accounting.receivables') }}">بدهکاران</a>
            </header>
            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead><tr><th>تاریخ</th><th>قبض</th><th>روش</th><th>مبلغ</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ jalali_like($payment->paid_at) }}</td>
                            <td>{{ $payment->reception?->ticket_no }}</td>
                            <td><span class="acc-chip">{{ $payment->methodLabel() }}</span></td>
                            <td class="acc-num">{{ toman($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">پرداختی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
