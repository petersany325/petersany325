@extends('layouts.app')
@section('title', $entry->entry_no.' | حسابداری')
@section('page_title', 'سند '.$entry->entry_no)
@section('window_title', 'سند حسابداری')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'سند '.$entry->entry_no,
    'accSub' => $entry->description,
])

<div class="acc-desk">
    <section class="acc-panel">
        <div class="detail-kv" style="margin-bottom:12px;">
            <div><span class="muted">تاریخ</span><div>{{ jalali_date($entry->entry_date) }}</div></div>
            <div><span class="muted">مبلغ</span><div>{{ number_format($entry->total_amount) }} تومان</div></div>
            <div><span class="muted">مشتری</span><div>{{ $entry->customer?->name ?: '—' }}</div></div>
            <div><span class="muted">قبض</span><div>
                @if($entry->reception)
                    <a href="{{ route('receptions.show', $entry->reception) }}">{{ $entry->reception->ticket_no }}</a>
                @else
                    —
                @endif
            </div></div>
            <div><span class="muted">منبع</span><div>{{ $entry->source_type }} #{{ $entry->source_id }}</div></div>
            <div><span class="muted">ثبت‌کننده</span><div>{{ $entry->creator?->name ?: 'سیستم' }}</div></div>
        </div>
        <div class="table-wrap">
            <table class="compact-table acc-table">
                <thead>
                <tr><th>حساب</th><th>شرح ردیف</th><th>بدهکار</th><th>بستانکار</th></tr>
                </thead>
                <tbody>
                @foreach($entry->lines as $line)
                    <tr>
                        <td dir="ltr">{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                        <td>{{ $line->memo ?: '—' }}</td>
                        <td class="acc-num">{{ $line->debit ? number_format($line->debit) : '—' }}</td>
                        <td class="acc-num">{{ $line->credit ? number_format($line->credit) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="actions" style="margin-top:10px;">
            <a class="btn btn-secondary" href="{{ route('accounting.journals') }}">بازگشت به اسناد</a>
        </div>
    </section>
</div>
@endsection
