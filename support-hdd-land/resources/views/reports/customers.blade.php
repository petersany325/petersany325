@extends('layouts.app')
@section('title', 'گزارش مشتریان | '.shop_name())
@section('page_title', 'گزارش مشتریان')
@section('window_title', 'جستجو و پرونده مشتری')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">جستجوی مشتری</h2>
    <p class="muted">نام، موبایل یا کد ملی را بزنید؛ با باز کردن پرونده، همه قبض‌ها، قطعات، پرداخت‌ها و بدهی را می‌بینید.</p>
    <form class="search-row" method="GET" action="{{ route('reports.customers') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="مثلاً یوسف یا 0912..." style="min-width:260px;max-width:420px;">
        <button class="btn btn-primary" type="submit">جستجو</button>
        @if($q !== '')
            <a class="btn btn-ghost" href="{{ route('reports.customers') }}">پاک کردن</a>
        @endif
    </form>

    @if($q !== '')
        <div class="table-wrap" style="margin-top:12px;">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>آدرس</th>
                    <th>تعداد قبض</th>
                    <th>پرداخت‌شده</th>
                    <th>بدهی</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($searchResults as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td dir="ltr">{{ $c->phone }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($c->address, 40) ?: '—' }}</td>
                        <td>{{ $c->receptions_count }}</td>
                        <td>{{ toman((int) ($c->paid_sum ?? 0)) }}</td>
                        <td>{{ toman((int) ($c->debt_sum ?? 0)) }}</td>
                        <td><a class="btn btn-primary" href="{{ route('reports.customers.show', $c) }}">پرونده کامل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">مشتری با این عبارت پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@if(\App\Support\ReportSettings::showCharts())
<div class="report-charts-row" style="margin-bottom:12px;">
    @include('reports._chart', [
        'id' => 'chartTopCustomers',
        'title' => 'پرمراجعه‌ترین مشتریان (بازه)',
        'labels' => $chartTopLabels,
        'values' => $chartTopValues,
        'type' => \App\Support\ReportSettings::chartType(),
    ])
    @include('reports._chart', [
        'id' => 'chartReferrals',
        'title' => 'نحوه آشنایی',
        'labels' => $chartRefLabels,
        'values' => $chartRefValues,
        'type' => 'doughnut',
    ])
</div>
@endif

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">پرمراجعه‌ترین‌ها در بازه</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>مشتری</th><th>مراجعه</th><th>پرداخت</th><th></th></tr></thead>
                <tbody>
                @forelse($topCustomers as $c)
                    <tr>
                        <td>{{ $c->name }}<div class="muted" dir="ltr">{{ $c->phone }}</div></td>
                        <td>{{ $c->visits }}</td>
                        <td>{{ toman((int) ($c->paid_sum ?? 0)) }}</td>
                        <td><a class="btn btn-ghost" href="{{ route('reports.customers.show', $c) }}">پرونده</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">در این بازه مراجعه‌ای نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <h3 style="margin-top:0;">نحوه آشنایی</h3>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>منبع</th><th>تعداد</th></tr></thead>
                    <tbody>
                    @foreach($referrals as $r)
                        <tr><td>{{ $r->name }}</td><td>{{ $r->customers_count }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <h3 style="margin-top:0;">انواع ایراد</h3>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>ایراد</th><th>تعداد</th></tr></thead>
                    <tbody>
                    @foreach($faults as $f)
                        <tr><td>{{ $f->name }}</td><td>{{ $f->jobs }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@include('reports._charts-boot')
