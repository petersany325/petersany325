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
            @php
                $rc = $customer->receptions->count();
                $confirm = $rc > 0
                    ? "مشتری «{$customer->name}» {$rc} قبض دارد. از فهرست حذف می‌شود ولی قبض‌ها می‌مانند. ادامه؟"
                    : "مشتری «{$customer->name}» حذف شود؟";
            @endphp
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="{{ $confirm }}">
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

    @php
        $debtSummary = app(\App\Services\CustomerDebtService::class)->summary($customer);
    @endphp
    @if($debtSummary['has_debt'])
        <div class="alert alert-error" style="margin-bottom:10px;">
            بدهکاری فعال: <strong>{{ number_format($debtSummary['total']) }} تومان</strong>
            ({{ $debtSummary['ticket_count'] }} قبض)
            @if($debtSummary['credit_total'] > 0)
                — از این مبلغ {{ number_format($debtSummary['credit_total']) }} تومان نسیه پس از تحویل است.
            @endif
            <a href="{{ route('accounting.receivables') }}" style="margin-right:8px;">بدهکاران حسابداری</a>
        </div>
    @endif

    <div class="detail-kv">
        <div><span class="muted">کد ملی</span><div>{{ $customer->national_code ?: '—' }}</div></div>
        <div><span class="muted">نحوه آشنایی</span><div>{{ $customer->referralSource?->name ?: '—' }}</div></div>
        <div><span class="muted">بدهی باز</span><div style="{{ $debtSummary['has_debt'] ? 'color:#b42318;font-weight:800;' : '' }}">{{ number_format($debtSummary['total']) }} تومان</div></div>
        <div style="grid-column:1/-1"><span class="muted">آدرس</span><div>{{ $customer->address ?: '—' }}</div></div>
        <div style="grid-column:1/-1"><span class="muted">یادداشت</span><div>{{ $customer->notes ?: '—' }}</div></div>
    </div>

    <h3 style="margin-top:10px;font-size:12.5px;">تاریخچه قبض‌ها</h3>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>شماره</th><th>کالا</th><th>وضعیت</th><th>مانده</th><th>تاریخ</th></tr></thead>
            <tbody>
            @forelse($customer->receptions as $item)
                @php $remain = $item->remainingAmount(); @endphp
                <tr>
                    <td><a href="{{ route('receptions.show', $item) }}">{{ $item->ticket_no }}</a></td>
                    <td>{{ $item->product_name }}</td>
                    <td>
                        <span class="badge badge-{{ $item->status }}">{{ $item->statusLabel() }}</span>
                        @if($item->settlement_mode === 'credit' && $remain > 0)
                            <span class="pill" style="background:#fde8e8;color:#9f1239;">نسیه</span>
                        @endif
                    </td>
                    <td style="{{ $remain > 0 ? 'color:#b42318;font-weight:700;' : '' }}">{{ number_format($remain) }}</td>
                    <td>{{ jalali_like($item->created_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">قبضی ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
