@extends('layouts.app')
@section('title', $customer->name.' | سرزمین هارد')
@section('page_title', 'پرونده مشتری')
@section('content')
<div class="panel detail-box">
    <div class="detail-box-head">
        <div>
            <h2>{{ $customer->name }}</h2>
            <p class="lead" style="margin:2px 0 0;" dir="ltr">{{ $customer->phone }} — {{ $customer->job ?: 'شغل نامشخص' }}</p>
        </div>
        <div class="report-head-actions">
            <a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">ویرایش</a>
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="مشتری «{{ $customer->name }}» حذف شود؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">حذف</button>
            </form>
            <a class="btn btn-ghost" href="{{ route('customers.index') }}">بازگشت</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="detail-kv">
        <div><span class="muted">کد ملی</span><div>{{ $customer->national_code ?: '—' }}</div></div>
        <div><span class="muted">نحوه آشنایی</span><div>{{ $customer->referralSource?->name ?: '—' }}</div></div>
        <div style="grid-column:1/-1"><span class="muted">آدرس</span><div>{{ $customer->address ?: '—' }}</div></div>
        <div style="grid-column:1/-1"><span class="muted">یادداشت</span><div>{{ $customer->notes ?: '—' }}</div></div>
    </div>

    <h3 style="margin-top:10px;font-size:12.5px;">تاریخچه قبض‌ها</h3>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>شماره</th><th>کالا</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
            <tbody>
            @forelse($customer->receptions as $item)
                <tr>
                    <td><a href="{{ route('receptions.show', $item) }}">{{ $item->ticket_no }}</a></td>
                    <td>{{ $item->product_name }}</td>
                    <td><span class="badge badge-{{ $item->status }}">{{ $item->statusLabel() }}</span></td>
                    <td>{{ jalali_like($item->created_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">قبضی ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
