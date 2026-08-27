@extends('layouts.app')
@section('title', 'بدهکاران | سرزمین هارد')
@section('page_title', 'بدهکاران مشتریان')
@section('window_title', 'حساب بدهکاران (۱۲۱۰)')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'بدهکاران',
    'accSub' => 'حساب دریافتنی مشتریان — مطابق استاندارد حسابداری تعمیرگاه (AR)',
])

<div class="acc-desk">
    <div class="acc-kpi-grid">
        <div class="acc-kpi tone-rose">
            <span class="acc-kpi-label">مانده دفتر ۱۲۱۰</span>
            <strong class="acc-kpi-value">{{ number_format($total) }}</strong>
            <span class="acc-kpi-foot">جمع بدهکاری از اسناد حسابداری</span>
        </div>
        <div class="acc-kpi tone-amber">
            <span class="acc-kpi-label">نسیه تحویل‌شده</span>
            <strong class="acc-kpi-value">{{ number_format($creditTotal ?? 0) }}</strong>
            <span class="acc-kpi-foot">{{ ($creditTickets ?? collect())->count() }} قبض با مانده پس از خروج</span>
        </div>
    </div>

    <div class="acc-panels" style="grid-template-columns:1fr;">
        <section class="acc-panel">
            <header class="acc-panel-head">
                <h3>بدهکاران از دفتر کل (حساب ۱۲۱۰)</h3>
            </header>
            <p class="muted" style="margin:0 0 8px;font-size:11.5px;">
                شناسایی درآمد: بدهکار ۱۲۱۰ / بستانکار درآمد. دریافت بعدی: بدهکار صندوق / بستانکار ۱۲۱۰.
            </p>
            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead>
                    <tr>
                        <th>مشتری</th>
                        <th>موبایل</th>
                        <th>بدهکار</th>
                        <th>بستانکار</th>
                        <th>مانده</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                @if($row['customer'])
                                    <a class="acc-link" href="{{ route('customers.show', $row['customer']) }}">{{ $row['customer']->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td dir="ltr">{{ $row['customer']?->phone ?: '—' }}</td>
                            <td class="acc-num">{{ number_format($row['debit']) }}</td>
                            <td class="acc-num">{{ number_format($row['credit']) }}</td>
                            <td class="acc-num" style="color:#b42318;font-weight:800;">{{ number_format($row['balance']) }}</td>
                            <td>
                                @if($row['customer'])
                                    <a class="btn btn-ghost btn-sm" href="{{ route('accounting.ledger', ['account' => '1210', 'q' => $row['customer']->id]) }}">دفتر</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">بدهکار بازی در دفتر ۱۲۱۰ نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="acc-panel">
            <header class="acc-panel-head">
                <h3>قبض‌های نسیه (تحویل با مانده)</h3>
            </header>
            <div class="table-wrap">
                <table class="compact-table acc-table">
                    <thead>
                    <tr>
                        <th>قبض</th>
                        <th>مشتری</th>
                        <th>تحویل</th>
                        <th>جمع</th>
                        <th>پرداخت‌شده</th>
                        <th>مانده نسیه</th>
                        <th>یادداشت تسویه</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse(($creditTickets ?? collect()) as $rx)
                        <tr>
                            <td><a class="acc-link" href="{{ route('receptions.show', $rx) }}">{{ $rx->ticket_no }}</a></td>
                            <td>
                                @if($rx->customer)
                                    <a href="{{ route('customers.show', $rx->customer) }}">{{ $rx->customer->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ jalali_like($rx->delivered_at) }}</td>
                            <td class="acc-num">{{ number_format((int) $rx->total_amount) }}</td>
                            <td class="acc-num">{{ number_format((int) $rx->paid_amount) }}</td>
                            <td class="acc-num" style="color:#b42318;font-weight:800;">{{ number_format($rx->remainingAmount()) }}</td>
                            <td>{{ $rx->settlement_note ?: ($rx->settlement_mode === 'credit' ? 'نسیه' : '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">قبض نسیه باز نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
