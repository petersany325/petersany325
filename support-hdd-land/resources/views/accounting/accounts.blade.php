@extends('layouts.app')
@section('title', 'سرفصل حساب‌ها | سرزمین هارد')
@section('page_title', 'سرفصل حساب‌ها')
@section('window_title', 'چارت حساب‌ها')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'سرفصل حساب‌ها',
    'accSub' => 'مانده جاری هر حساب در دفتر کل',
])

<div class="acc-desk">
    <section class="acc-panel">
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr>
                    <th>کد</th>
                    <th>نام</th>
                    <th>ماهیت</th>
                    <th>بدهکار</th>
                    <th>بستانکار</th>
                    <th>مانده</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($accounts as $row)
                    @php $a = $row['model']; @endphp
                    <tr>
                        <td dir="ltr"><strong>{{ $a->code }}</strong></td>
                        <td>{{ $a->name }}</td>
                        <td>{{ $a->nature === 'credit' ? 'بستانکار' : 'بدهکار' }}</td>
                        <td class="acc-num">{{ number_format($row['debit']) }}</td>
                        <td class="acc-num">{{ number_format($row['credit']) }}</td>
                        <td class="acc-num">{{ number_format($row['balance']) }}</td>
                        <td><a class="acc-link" href="{{ route('accounting.ledger', ['account' => $a->code]) }}">دفتر معین</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
