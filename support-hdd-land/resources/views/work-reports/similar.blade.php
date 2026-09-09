@extends('layouts.app')
@section('title', 'شرح‌کارهای مشابه | '.shop_name())
@section('page_title', 'تجربیات مشابه برای '.$reception->ticket_no)
@section('content')
<div class="panel">
    <div class="actions" style="margin:0 0 10px;">
        <a class="btn btn-ghost" href="{{ route('receptions.show', $reception) }}">بازگشت به قبض</a>
    </div>
    <p class="lead">ایراد/مدل مشابه با قبض‌های قبلی — برای استفاده از تجربیات گذشته</p>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>تاریخ</th><th>قبض</th><th>مدل</th><th>نویسنده</th><th>شرح</th></tr></thead>
            <tbody>
            @forelse($similar as $r)
                <tr>
                    <td>{{ jalali_like($r->created_at) }}</td>
                    <td><a href="{{ route('receptions.show', $r->reception_id) }}">{{ $r->reception?->ticket_no }}</a></td>
                    <td>{{ $r->reception?->brand }} {{ $r->reception?->model }}</td>
                    <td>{{ $r->technician?->name ?: $r->user?->name }}</td>
                    <td><strong>{{ $r->summary }}</strong><div class="muted">{{ $r->details }}</div></td>
                </tr>
            @empty
                <tr><td colspan="5">مورد مشابهی پیدا نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
