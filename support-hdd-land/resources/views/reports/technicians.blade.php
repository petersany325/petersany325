@extends('layouts.app')
@section('title', 'عملکرد تعمیرکاران | سرزمین هارد')
@section('page_title', 'گزارش عملکرد تعمیرکاران')
@section('content')
<div class="panel">
    <h2>عملکرد تعمیرکاران</h2>
    <form class="search-row" method="GET">
        <input type="date" name="from" value="{{ $from }}">
        <input type="date" name="to" value="{{ $to }}">
        <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>تعمیرکار</th><th>تخصص</th><th>کل کار</th><th>تحویل‌شده</th><th>دست تعمیر</th><th>جمع اجرت</th><th>کمیسیون%</th><th>مبلغ کمیسیون</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->specialty ?: '—' }}</td>
                    <td>{{ $row->jobs_count }}</td>
                    <td>{{ $row->delivered_count }}</td>
                    <td>{{ $row->in_hand_count }}</td>
                    <td>{{ toman($row->labor_sum) }}</td>
                    <td>{{ $row->commission_percent }}٪</td>
                    <td>{{ toman($row->commission_sum ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
