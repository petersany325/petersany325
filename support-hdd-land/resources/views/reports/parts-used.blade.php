@extends('layouts.app')
@section('title', 'کالای خرج‌شده | سرزمین هارد')
@section('page_title', 'گزارش کالاهای خرج‌شده')
@section('content')
<div class="panel">
    <h2>کالا/قطعات خرج‌شده در تاریخ</h2>
    <form class="search-row" method="GET">
        <input type="date" name="from" value="{{ $from }}">
        <input type="date" name="to" value="{{ $to }}">
        <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>نام قطعه</th><th>تعداد مصرف</th><th>مبلغ</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ $row->qty }}</td>
                    <td>{{ toman($row->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">در این بازه مصرفی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
