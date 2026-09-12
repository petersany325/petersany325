@extends('layouts.app')
@section('title', 'ارزش موجودی انبار | '.shop_name())
@section('page_title', 'ارزش‌گذاری موجودی')
@section('window_title', 'تراز انبار به بهای خرید و فروش')

@section('content')
@include('parts._nav', [
    'whTitle' => 'ارزش موجودی انبار',
    'whSub' => 'تراز ریالی قطعات فعال — مبنای حساب ۱۳۱۰',
])

<div class="wh-desk">
    <div class="wh-kpi-grid">
        <div class="wh-kpi tone-teal"><span>جمع تعداد</span><strong>{{ number_format($totals['qty']) }}</strong></div>
        <div class="wh-kpi tone-amber"><span>ارزش خرید</span><strong>{{ number_format($totals['cost']) }}</strong></div>
        <div class="wh-kpi tone-blue"><span>ارزش فروش</span><strong>{{ number_format($totals['sale']) }}</strong></div>
        <div class="wh-kpi tone-rose"><span>حاشیه بالقوه</span><strong>{{ number_format($totals['margin']) }}</strong></div>
    </div>

    <div class="panel">
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>کد</th>
                    <th>کالا</th>
                    <th>موجودی</th>
                    <th>بهای خرید</th>
                    <th>فی فروش</th>
                    <th>ارزش خرید</th>
                    <th>ارزش فروش</th>
                    <th>حاشیه</th>
                </tr>
                </thead>
                <tbody>
                @forelse($parts as $part)
                    <tr class="{{ $part->isLowStock() ? 'wh-row-low' : '' }}">
                        <td dir="ltr">{{ $part->code ?: '—' }}</td>
                        <td><a href="{{ route('parts.show', $part) }}">{{ $part->name }}</a></td>
                        <td>{{ number_format($part->stock) }}</td>
                        <td>{{ toman($part->purchase_price) }}</td>
                        <td>{{ toman($part->sale_price) }}</td>
                        <td>{{ toman($part->value_cost) }}</td>
                        <td>{{ toman($part->value_sale) }}</td>
                        <td class="{{ $part->margin < 0 ? 'wh-neg' : 'wh-pos' }}">{{ toman($part->margin) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">کالای فعالی نیست.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2">جمع</th>
                    <th>{{ number_format($totals['qty']) }}</th>
                    <th></th><th></th>
                    <th>{{ toman($totals['cost']) }}</th>
                    <th>{{ toman($totals['sale']) }}</th>
                    <th>{{ toman($totals['margin']) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
