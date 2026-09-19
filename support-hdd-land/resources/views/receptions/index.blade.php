@extends('layouts.app')

@section('title', 'لیست قبض‌ها | سرزمین هارد')
@section('page_title', 'قبض‌های ورودی و خروجی')
@section('window_title', 'لیست قبض‌ها')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>قبض‌ها</h2>
            <p class="lead">لیست سریع پذیرش‌ها — برای گزارش کامل از «جستجوی قبض» استفاده کنید.</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-secondary" href="{{ route('receptions.search') }}">جستجوی قبض / گزارش کامل</a>
            <a class="btn btn-primary" href="{{ route('receptions.create') }}">پذیرش جدید</a>
        </div>
    </div>

    <form class="search-row" method="GET">
        <input type="text" name="q" value="{{ $q }}" placeholder="نام مشتری، موبایل، شماره قبض، سریال..." data-barcode data-search-barcode autocomplete="off">
        <select name="status">
            <option value="">همه وضعیت‌ها</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">فیلتر لیست</button>
        @if($q !== '')
            <a class="btn btn-primary" href="{{ route('receptions.search', array_filter(['q' => $q, 'status' => $status])) }}">نمایش گزارش کامل نتایج</a>
        @endif
    </form>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>شماره</th>
                <th>مشتری</th>
                <th>کالا / مدل</th>
                <th>سریال</th>
                <th>تعمیرکار</th>
                <th>بیعانه</th>
                <th>مانده</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($receptions as $item)
                <tr>
                    <td>{{ $item->ticket_no }}</td>
                    <td>{{ $item->customer?->name }}<div class="muted">{{ $item->customer?->phone }}</div></td>
                    <td>{{ $item->product_name }} {{ $item->model }}</td>
                    <td>{{ $item->serial_number ?: '—' }}</td>
                    <td>{{ $item->technician?->name ?? '—' }}</td>
                    <td>{{ toman($item->deposit) }}</td>
                    <td>{{ toman($item->remainingAmount()) }}</td>
                    <td><span class="badge badge-{{ $item->status }}">{{ $item->statusLabel() }}</span></td>
                    <td>
                        <a class="btn btn-secondary" href="{{ route('receptions.show', $item) }}">جزئیات</a>
                        <a class="btn btn-ghost" href="{{ route('receptions.search', ['q' => $item->ticket_no]) }}">گزارش</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">موردی یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $receptions->links('partials.pagination') }}
</div>
@endsection
