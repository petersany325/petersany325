@extends('layouts.app')
@section('title', 'انبارگردانی | '.shop_name())
@section('page_title', 'انبارگردانی و شمارش موجودی')
@section('content')
<div class="panel">
    <h2>انبارگردانی</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('stocktakes.store') }}" class="accept-row accept-row-3" style="align-items:end;">
        @csrf
        <div>
            <label>انبار (اختیاری = همه)</label>
            <select name="warehouse_id"><option value="">همه</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div><label>یادداشت</label><input name="note"></div>
        <div><button class="btn btn-primary" type="submit">شروع انبارگردانی</button></div>
    </form>
    <div class="table-wrap" style="margin-top:12px;">
        <table class="data">
            <thead><tr><th>سند</th><th>انبار</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
            @foreach($items as $s)
                <tr>
                    <td dir="ltr">{{ $s->doc_no }}</td>
                    <td>{{ $s->warehouse?->name ?: 'همه' }}</td>
                    <td>{{ $s->status === 'posted' ? 'ثبت‌شده' : 'پیش‌نویس' }}</td>
                    <td>{{ jalali_like($s->created_at) }}</td>
                    <td><a class="btn btn-ghost" href="{{ route('stocktakes.show', $s) }}">باز کردن</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $items->links('partials.pagination') }}
</div>
@endsection
