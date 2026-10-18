@extends('layouts.app')
@section('title', 'پرینت کلی قبض | سرزمین هارد')
@section('page_title', 'پرینت کلی قبض')
@section('window_title', 'انتخاب چند قبض و چاپ یکجا')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">پرینت کلی قبض</h2>
    <p class="muted" style="margin-top:0;">
        چند قبض را انتخاب کنید تا در یک پنجره و یک دستور چاپ، پشت‌سرهم چاپ شوند.
        حداکثر {{ $maxSelect }} قبض در هر چاپ.
    </p>

    <form class="search-row" method="GET" action="{{ route('reports.receipt-print') }}" style="flex-wrap:wrap;gap:8px;">
        <input type="search" name="q" value="{{ $q }}" placeholder="شماره قبض، تیکت، سریال، موبایل، نام، کد گروه..." style="min-width:260px;max-width:420px;">
        <input type="date" name="from" value="{{ $from }}" title="از تاریخ">
        <input type="date" name="to" value="{{ $to }}" title="تا تاریخ">
        <button class="btn btn-primary" type="submit">جستجو</button>
        @if($q !== '' || request()->filled('from') || request()->filled('to'))
            <a class="btn btn-ghost" href="{{ route('reports.receipt-print') }}">پاک کردن</a>
        @endif
    </form>
</div>

<form id="bulk-print-form" method="POST" action="{{ route('receptions.print-bulk') }}" target="_blank">
    @csrf
    <div class="panel">
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <label style="display:flex;gap:6px;align-items:center;cursor:pointer;">
                    <input type="checkbox" id="select-all-receipts">
                    <span>انتخاب همهٔ لیست</span>
                </label>
                <span class="muted" id="selected-count">۰ انتخاب‌شده</span>
            </div>
            <button class="btn btn-primary" type="submit" id="bulk-print-btn" disabled>چاپ انتخاب‌شده‌ها</button>
        </div>

        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>تیکت</th>
                    <th>قبض</th>
                    <th>مشتری</th>
                    <th>سریال</th>
                    <th>وضعیت</th>
                    <th>تاریخ پذیرش</th>
                    <th>گروه</th>
                </tr>
                </thead>
                <tbody>
                @forelse($receptions as $r)
                    <tr>
                        <td>
                            <input type="checkbox" class="receipt-check" name="ids[]" value="{{ $r->id }}">
                        </td>
                        <td>
                            <a href="{{ route('receptions.show', $r) }}" target="_blank">{{ $r->ticket_no }}</a>
                        </td>
                        <td>{{ $r->receipt_no ?: '—' }}</td>
                        <td>
                            {{ $r->customer?->name ?: '—' }}
                            <div class="muted" dir="ltr">{{ $r->customer?->phone }}</div>
                        </td>
                        <td dir="ltr">{{ $r->serial_number ?: '—' }}</td>
                        <td>{{ $r->statusLabel() }}</td>
                        <td>{{ jalali_like($r->received_at) }}</td>
                        <td>{{ $r->batch_code ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">قبضی با این فیلتر پیدا نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($receptions, 'links'))
            <div style="margin-top:12px;">{{ $receptions->withQueryString()->links() }}</div>
        @endif
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const maxSelect = {{ (int) $maxSelect }};
    const checks = Array.from(document.querySelectorAll('.receipt-check'));
    const selectAll = document.getElementById('select-all-receipts');
    const countEl = document.getElementById('selected-count');
    const btn = document.getElementById('bulk-print-btn');
    const form = document.getElementById('bulk-print-form');

    function selected() {
        return checks.filter(c => c.checked);
    }

    function refresh() {
        const n = selected().length;
        countEl.textContent = n + ' انتخاب‌شده';
        btn.disabled = n < 1;
        if (selectAll) {
            selectAll.checked = checks.length > 0 && n === checks.length;
            selectAll.indeterminate = n > 0 && n < checks.length;
        }
    }

    checks.forEach(c => c.addEventListener('change', function () {
        if (selected().length > maxSelect) {
            c.checked = false;
            alert('حداکثر ' + maxSelect + ' قبض در هر چاپ مجاز است.');
        }
        refresh();
    }));

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const on = selectAll.checked;
            let taken = 0;
            checks.forEach(c => {
                if (on && taken >= maxSelect) {
                    c.checked = false;
                    return;
                }
                c.checked = on;
                if (on) taken++;
            });
            if (on && checks.length > maxSelect) {
                alert('فقط ' + maxSelect + ' قبض اول انتخاب شد.');
            }
            refresh();
        });
    }

    form.addEventListener('submit', function (e) {
        const n = selected().length;
        if (n < 1) {
            e.preventDefault();
            return;
        }
        if (n > maxSelect) {
            e.preventDefault();
            alert('حداکثر ' + maxSelect + ' قبض در هر چاپ مجاز است.');
        }
    });

    refresh();
})();
</script>
@endpush
