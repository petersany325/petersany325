@extends('layouts.app')
@section('title', 'گزارش صندوق و دریافت‌ها | سرزمین هارد')
@section('page_title', 'گزارش صندوق / دریافت‌ها')
@section('window_title', 'نقد، کارت، زرین‌پال و بدهکاران')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">صندوق و دریافت‌ها</h2>
    <p class="muted" style="margin-top:0;">گزارش روزانه دریافت‌ها به تفکیک روش پرداخت — مکمل حسابداری.</p>
    @include('reports._filters')
    <div class="stats stats-compact">
        <div class="stat"><div class="label">دریافت دوره</div><div class="value">{{ toman((int) $totalIn) }}</div></div>
        <div class="stat"><div class="label">عودت</div><div class="value">{{ toman((int) $totalRefund) }}</div></div>
        <div class="stat"><div class="label">خالص</div><div class="value">{{ toman((int) $totalIn - (int) $totalRefund) }}</div></div>
    </div>
</div>

<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">به تفکیک روش</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>روش</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($byMethod as $row)
                    <tr>
                        <td>{{ \App\Models\Payment::METHODS[$row->method] ?? $row->method }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ toman((int) $row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">پرداختی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <h3>به تفکیک نوع</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>نوع</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @foreach($byType as $row)
                    <tr>
                        <td>{{ \App\Models\Payment::TYPES[$row->type] ?? $row->type }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ toman((int) $row->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">روزانه (خالص)</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>روز</th><th>تعداد</th><th>خالص</th></tr></thead>
                <tbody>
                @forelse($daily as $row)
                    <tr>
                        <td>{{ $row->day }}</td>
                        <td>{{ $row->total }}</td>
                        <td>{{ toman((int) $row->net) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <h3>درگاه زرین‌پال</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>وضعیت</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($gateway as $g)
                    <tr>
                        <td>{{ \App\Models\GatewayTransaction::STATUSES[$g->status] ?? $g->status }}</td>
                        <td>{{ $g->total }}</td>
                        <td>{{ toman((int) $g->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">تراکنشی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">بیشترین مانده بدهکار</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>قبض</th><th>مشتری</th><th>مانده</th></tr></thead>
                <tbody>
                @forelse($receivables as $r)
                    <tr>
                        <td><a href="{{ route('receptions.show', $r) }}">{{ $r->ticket_no }}</a></td>
                        <td>{{ $r->customer?->name }}</td>
                        <td>{{ toman($r->remainingAmount()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">مانده‌ای نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">آخرین دریافت‌ها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>زمان</th><th>مشتری</th><th>روش</th><th>مبلغ</th></tr></thead>
                <tbody>
                @forelse($recent as $p)
                    <tr>
                        <td>{{ optional($p->paid_at)->format('Y/m/d H:i') }}</td>
                        <td>{{ $p->customer?->name }}</td>
                        <td>{{ $p->methodLabel() }}</td>
                        <td>{{ toman((int) $p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
