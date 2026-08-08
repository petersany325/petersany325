@extends('layouts.app')
@section('title', 'تحویل گروهی | سرزمین هارد')
@section('page_title', 'تحویل گروهی')
@section('window_title', 'تحویل گروهی قبض')

@section('content')
<div class="panel compact-panel" id="group-delivery"
     data-lookup-url="{{ route('deliveries.lookup') }}">
    <div class="compact-head">
        <div>
            <h2 style="margin:0;font-size:15px;">تحویل گروهی</h2>
            <p class="lead" style="margin:2px 0 0;">نام و موبایل تحویل‌گیرنده + شماره قبض‌ها → هزینه → تسویه (نقد/نسیه/بخشش) → خروج نهایی</p>
        </div>
    </div>

    <form method="POST" action="{{ route('deliveries.store') }}" id="group-delivery-form">
        @csrf
        <div class="accept-row accept-row-3" style="margin-bottom:6px;">
            <div>
                <label>نام تحویل‌گیرنده</label>
                <input type="text" name="pickup_name" id="pickup-name" value="{{ old('pickup_name') }}" required placeholder="نام شخص">
            </div>
            <div>
                <label>موبایل</label>
                <input type="text" name="pickup_phone" id="pickup-phone" value="{{ old('pickup_phone') }}" required placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>تعداد / شماره قبض‌ها</label>
                <input type="text" id="ticket-input" placeholder="SH-... یا شماره قبض — با فاصله یا ویرگول" dir="ltr" style="text-align:left;" value="{{ old('tickets') }}">
            </div>
        </div>
        <div class="actions" style="margin:4px 0 8px;">
            <button type="button" class="btn btn-secondary" id="lookup-tickets-btn">بارگذاری کارتابل</button>
            <span class="lookup-status" id="delivery-status"></span>
        </div>

        <div class="table-wrap">
            <table class="compact-table" id="delivery-cart">
                <thead>
                <tr>
                    <th></th>
                    <th>قبض</th>
                    <th>مشتری</th>
                    <th>سریال</th>
                    <th>وضعیت</th>
                    <th>هزینه (تومان)</th>
                    <th>مانده</th>
                    <th>وضعیت مبلغ</th>
                </tr>
                </thead>
                <tbody id="delivery-cart-body">
                <tr class="empty-row"><td colspan="8" class="muted">هنوز قبضی بارگذاری نشده.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="accept-row accept-row-3" style="margin-top:8px;">
            <div>
                <label>تسویه قبض‌های مانده‌دار</label>
                <select name="settlement_mode">
                    <option value="">فقط قبض‌های تسویه‌شده (مانده صفر)</option>
                    <option value="credit" @selected(old('settlement_mode') === 'credit')>نسیه — بدهکار شدن مشتری</option>
                    <option value="waive" @selected(old('settlement_mode') === 'waive')>بخشش مانده / بدون هزینه</option>
                </select>
            </div>
            <div>
                @include('partials.toggle', ['name' => 'send_sms', 'label' => 'ارسال پیامک تحویل', 'checked' => true, 'on' => 'برود', 'off' => 'نرود'])
            </div>
            <div>
                <label>یادداشت / دلیل نسیه یا بخشش</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="برای نسیه یا بخشش الزامی است">
            </div>
        </div>

        <div class="sms-actions" style="margin-top:8px;">
            <button class="btn btn-primary" type="submit" id="final-deliver-btn">تایید منو و خروج نهایی</button>
            <span class="muted" id="cart-summary">۰ قبض</span>
        </div>
    </form>
</div>

@if($recent->count())
<div class="panel compact-panel" style="margin-top:8px;">
    <h3 style="margin:0 0 6px;font-size:13px;">آخرین تحویل‌های گروهی</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead><tr><th>کد</th><th>گیرنده</th><th>تعداد</th><th>جمع</th><th>زمان</th></tr></thead>
            <tbody>
            @foreach($recent as $b)
                <tr>
                    <td>{{ $b->batch_code }}</td>
                    <td>{{ $b->pickup_name }} <span class="muted">{{ $b->pickup_phone }}</span></td>
                    <td>{{ $b->ticket_count }}</td>
                    <td>{{ number_format($b->total_amount) }}</td>
                    <td>{{ jalali_like($b->delivered_at) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('group-delivery');
    if (!root) return;
    var url = root.getAttribute('data-lookup-url');
    var body = document.getElementById('delivery-cart-body');
    var statusEl = document.getElementById('delivery-status');
    var summary = document.getElementById('cart-summary');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function setStatus(t, type) {
        statusEl.textContent = t || '';
        statusEl.className = 'lookup-status' + (type ? ' is-' + type : '');
    }

    function render(items) {
        body.innerHTML = '';
        if (!items.length) {
            body.innerHTML = '<tr class="empty-row"><td colspan="8" class="muted">قبضی پیدا نشد.</td></tr>';
            summary.textContent = '۰ قبض';
            return;
        }
        var missing = 0;
        var unsettled = 0;
        items.forEach(function (it) {
            if (!it.has_cost) missing++;
            if ((it.remaining || 0) > 0 && !it.already_delivered) unsettled++;
            var tr = document.createElement('tr');
            if (!it.has_cost || (it.remaining || 0) > 0) tr.className = 'row-warn';
            if (it.already_delivered) tr.className += ' row-done';
            tr.innerHTML =
                '<td><input type="checkbox" name="ticket_ids[]" value="' + it.id + '" checked></td>' +
                '<td>' + it.ticket_no + '<div class="muted">' + (it.receipt_no || '') + '</div></td>' +
                '<td>' + (it.customer || '—') + '</td>' +
                '<td dir="ltr" style="text-align:left;">' + (it.serial || '—') + '</td>' +
                '<td>' + (it.status_label || it.status) + '</td>' +
                '<td><input type="number" name="costs[' + it.id + ']" min="0" value="' + (it.total_amount || it.labor_cost || 0) + '" style="width:110px;direction:ltr;text-align:left;"></td>' +
                '<td>' + ((it.remaining || 0) > 0 ? '<strong>' + Number(it.remaining).toLocaleString('en-US') + '</strong>' : '۰') + '</td>' +
                '<td>' + (it.has_cost
                    ? ((it.remaining || 0) > 0 ? '<span class="pill pill-off">مانده‌دار</span>' : '<span class="pill pill-ok">تسویه</span>')
                    : '<span class="pill pill-off">نامشخص</span>') +
                  (it.already_delivered ? ' <span class="pill">قبلاً تحویل</span>' : '') + '</td>';
            body.appendChild(tr);
        });
        summary.textContent = items.length + ' قبض'
            + (missing ? ' — ' + missing + ' بدون هزینه' : '')
            + (unsettled ? ' — ' + unsettled + ' مانده‌دار' : '');
        setStatus(
            unsettled ? 'قبض مانده‌دار: نسیه/بخشش را انتخاب کنید یا اول در صفحه قبض دریافت کامل بزنید.'
                : (missing ? 'قبل از تحویل، هزینه را ثبت کنید یا بخشش را انتخاب کنید.' : 'آماده تایید نهایی.'),
            (missing || unsettled) ? 'warn' : 'ok'
        );
    }

    function lookup() {
        var tickets = document.getElementById('ticket-input').value.trim();
        if (!tickets) { setStatus('شماره قبض را وارد کنید.', 'error'); return; }
        setStatus('در حال بارگذاری...', 'info');
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tickets: tickets })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { setStatus(data.message || 'خطا', 'error'); return; }
                render(data.items || []);
            })
            .catch(function () { setStatus('خطا در ارتباط.', 'error'); });
    }

    document.getElementById('lookup-tickets-btn').addEventListener('click', lookup);
    document.getElementById('ticket-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); lookup(); }
    });

    document.getElementById('group-delivery-form').addEventListener('submit', function (e) {
        var checked = body.querySelectorAll('input[name="ticket_ids[]"]:checked');
        if (!checked.length) {
            e.preventDefault();
            setStatus('حداقل یک قبض را تیک بزنید.', 'error');
            return;
        }
        var modeEl = document.querySelector('select[name="settlement_mode"]');
        var mode = modeEl ? modeEl.value : '';
        var unsettled = false;
        checked.forEach(function (cb) {
            var row = cb.closest('tr');
            if (row && row.querySelector('.pill-off')) unsettled = true;
        });
        if (unsettled && !mode) {
            e.preventDefault();
            setStatus('قبض مانده‌دار یا بدون هزینه دارید. نسیه/بخشش را انتخاب کنید یا اول تسویه کنید.', 'error');
        }
    });
})();
</script>
@endpush
