@extends('layouts.app')
@section('title', 'گزارش کاربران | سرزمین هارد')
@section('page_title', 'گزارش کاربران / مشتریان')
@section('content')
<div class="split-2">
    <div class="panel">
        <h2>پرمراجعه‌ترین مشتریان</h2>
        <form class="search-row" method="GET">
            <input type="date" name="from" value="{{ $from }}">
            <input type="date" name="to" value="{{ $to }}">
            <button class="btn btn-secondary" type="submit">اعمال</button>
        </form>
        <div class="table-wrap">
            <table style="min-width:0;">
                <thead><tr><th>مشتری</th><th>تلفن</th><th>مراجعه</th></tr></thead>
                <tbody>
                @forelse($topCustomers as $c)
                    <tr><td>{{ $c->name }}</td><td>{{ $c->phone }}</td><td>{{ $c->visits }}</td></tr>
                @empty
                    <tr><td colspan="3">داده‌ای نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <h3>نحوه آشنایی</h3>
            <div class="table-wrap">
                <table style="min-width:0;">
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
            <h3>انواع ایراد</h3>
            <div class="table-wrap">
                <table style="min-width:0;">
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
