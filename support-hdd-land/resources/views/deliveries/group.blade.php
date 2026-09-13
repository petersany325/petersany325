@extends('layouts.app')
@section('title', 'تحویل گروهی | '.shop_name())
@section('page_title', 'تحویل گروهی')
@section('window_title', 'تحویل گروهی قبض')

@section('content')
<div class="panel compact-panel" id="group-delivery"
     data-lookup-url="{{ route('deliveries.lookup') }}"
     data-customers-url="{{ route('customers.suggest') }}">
    <div class="compact-head">
        <div>
            <h2 style="margin:0;font-size:15px;">تحویل گروهی</h2>
            <p class="lead" style="margin:2px 0 0;">نام مشتری را بنویسید → انتخاب از لیست → همه قبض‌های همان شخص بارگذاری می‌شود (موبایل خودکار پر می‌شود)</p>
        </div>
    </div>

    <form method="POST" action="{{ route('deliveries.store') }}" id="group-delivery-form">
        @csrf
        <input type="hidden" name="customer_id" id="pickup-customer-id" value="">
        <div class="accept-row accept-row-3" style="margin-bottom:6px;">
            <div style="position:relative;">
                <label>نام تحویل‌گیرنده / جستجوی مشتری</label>
                <input type="text" name="pickup_name" id="pickup-name" value="{{ old('pickup_name') }}" required
                       placeholder="حداقل ۱ حرف از نام…" autocomplete="off">
                <div class="customer-pick-list" id="pickup-pick-list" hidden></div>
            </div>
            <div>
                <label>موبایل</label>
                <input type="text" name="pickup_phone" id="pickup-phone" value="{{ old('pickup_phone') }}" required placeholder="با انتخاب نام پر می‌شود" dir="ltr" style="text-align:left;" data-ascii-en>
            </div>
            <div>
                <label>شماره قبض دستی (اختیاری)</label>
                <input type="text" id="ticket-input" placeholder="اگر مشتری انتخاب نشد: SH-... یا شماره قبض" dir="ltr" style="text-align:left;" value="{{ old('tickets') }}">
            </div>
        </div>
        <div class="actions" style="margin:4px 0 8px;">
            <button type="button" class="btn btn-primary" id="load-customer-tickets-btn">بارگذاری قبض‌های این مشتری</button>
            <button type="button" class="btn btn-secondary" id="lookup-tickets-btn">بارگذاری با شماره قبض</button>
            <span class="lookup-status" id="delivery-status"></span>
        </div>

        <div class="table-wrap">
            <table class="compact-table" id="delivery-cart">
                <thead>
                <tr>
                    <th></th>
                    <th>شماره قبض</th>
                    <th>تیکت</th>
                    <th>مشتری</th>
                    <th>سریال</th>
                    <th>وضعیت</th>
                    <th>هزینه (تومان)</th>
                    <th>مانده</th>
                    <th>وضعیت مبلغ</th>
                </tr>
                </thead>
                <tbody id="delivery-cart-body">
                <tr class="empty-row"><td colspan="9" class="muted">مشتری را انتخاب کنید تا لیست قبض‌هایش بیاید.</td></tr>
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
            <button class="btn btn-primary" type="submit" id="final-deliver-btn" data-confirm="تسویه گروهی، خروج کالا و تحویل نهایی ثبت شود؟">تأیید تسویه / خروج کالا / تحویل</button>
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
    var customersUrl = root.getAttribute('data-customers-url') || '';
    var body = document.getElementById('delivery-cart-body');
    var statusEl = document.getElementById('delivery-status');
    var summary = document.getElementById('cart-summary');
    var nameInput = document.getElementById('pickup-name');
    var phoneInput = document.getElementById('pickup-phone');
    var customerIdInput = document.getElementById('pickup-customer-id');
    var pickList = document.getElementById('pickup-pick-list');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var nameTimer = null;
    var nameSeq = 0;

    function setStatus(t, type) {
        statusEl.textContent = t || '';
        statusEl.className = 'lookup-status' + (type ? ' is-' + type : '');
    }

    function clearPick() {
        if (!pickList) return;
        pickList.innerHTML = '';
        pickList.hidden = true;
    }

    function selectCustomer(c) {
        if (!c) return;
        if (customerIdInput) customerIdInput.value = c.id || '';
        if (nameInput) nameInput.value = c.display_name || c.name || '';
        if (phoneInput) phoneInput.value = c.phone || '';
        clearPick();
        setStatus('مشتری انتخاب شد — در حال بارگذاری قبض‌ها…', 'info');
        loadByCustomer();
    }

    function renderPick(list, q) {
        if (!pickList) return;
        pickList.innerHTML = '';
        if (!list || !list.length) {
            pickList.hidden = false;
            var empty = document.createElement('div');
            empty.className = 'customer-pick-empty';
            empty.textContent = q ? 'مشتری با این نام پیدا نشد.' : 'نام را بنویسید.';
            pickList.appendChild(empty);
            return;
        }
        list.forEach(function (c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'customer-pick-item';
            var name = document.createElement('span');
            name.className = 'customer-pick-name';
            name.textContent = c.display_name || c.name || 'بدون نام';
            var meta = document.createElement('span');
            meta.className = 'customer-pick-meta';
            var bits = [];
            if (c.phone) bits.push(c.phone);
            if (typeof c.visits !== 'undefined') bits.push(c.visits + ' مراجعه');
            if (c.is_blacklisted) bits.push('لیست سیاه');
            meta.textContent = bits.join(' · ') || '—';
            btn.appendChild(name);
            btn.appendChild(meta);
            btn.addEventListener('click', function () { selectCustomer(c); });
            pickList.appendChild(btn);
        });
        pickList.hidden = false;
    }

    function searchNames(immediate) {
        if (!nameInput || !customersUrl) return;
        var q = (nameInput.value || '').trim();
        if (nameTimer) { clearTimeout(nameTimer); nameTimer = null; }
        var run = function () {
            q = (nameInput.value || '').trim();
            if (q.length < 1) { clearPick(); return; }
            var seq = ++nameSeq;
            fetch(customersUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (seq !== nameSeq) return;
                    renderPick((data && data.customers) || [], q);
                })
                .catch(function () {});
        };
        if (immediate) run(); else nameTimer = setTimeout(run, 220);
    }

    function render(items) {
        body.innerHTML = '';
        if (!items.length) {
            body.innerHTML = '<tr class="empty-row"><td colspan="9" class="muted">قبضی پیدا نشد.</td></tr>';
            summary.textContent = '۰ قبض';
            return;
        }
        var missing = 0;
        var unsettled = 0;
        items.forEach(function (it) {
            if (!it.has_cost && !it.already_delivered) missing++;
            if ((it.remaining || 0) > 0 && !it.already_delivered) unsettled++;
            var tr = document.createElement('tr');
            if (!it.has_cost || (it.remaining || 0) > 0) tr.className = 'row-warn';
            if (it.already_delivered) tr.className += ' row-done';
            var check = it.already_delivered ? '' : ' checked';
            var disabled = it.already_delivered ? ' disabled' : '';
            tr.innerHTML =
                '<td><input type="checkbox" name="ticket_ids[]" value="' + it.id + '"' + check + disabled + '></td>' +
                '<td dir="ltr" style="text-align:left;font-weight:700;">' + (it.receipt_no || '—') + '</td>' +
                '<td>' + it.ticket_no + '</td>' +
                '<td>' + (it.customer || '—') + '</td>' +
                '<td dir="ltr" style="text-align:left;">' + (it.serial || '—') + '</td>' +
                '<td>' + (it.status_label || it.status) + '</td>' +
                '<td><input type="number" name="costs[' + it.id + ']" min="0" value="' + (it.total_amount || it.labor_cost || 0) + '" style="width:110px;direction:ltr;text-align:left;"' + (it.already_delivered ? ' disabled' : '') + '></td>' +
                '<td>' + ((it.remaining || 0) > 0 ? '<strong>' + Number(it.remaining).toLocaleString('en-US') + '</strong>' : '۰') + '</td>' +
                '<td>' + (it.has_cost
                    ? ((it.remaining || 0) > 0 ? '<span class="pill pill-off">مانده‌دار</span>' : '<span class="pill pill-ok">تسویه</span>')
                    : '<span class="pill pill-off">نامشخص</span>') +
                  (it.already_delivered ? ' <span class="pill">قبلاً تحویل</span>' : '') + '</td>';
            body.appendChild(tr);
        });
        var openCount = items.filter(function (it) { return !it.already_delivered; }).length;
        summary.textContent = openCount + ' قبض باز از ' + items.length
            + (missing ? ' — ' + missing + ' بدون هزینه' : '')
            + (unsettled ? ' — ' + unsettled + ' مانده‌دار' : '');
        setStatus(
            unsettled ? 'قبض مانده‌دار: نسیه/بخشش را انتخاب کنید یا اول در صفحه قبض دریافت کامل بزنید.'
                : (missing ? 'قبل از تحویل، هزینه را ثبت کنید یا بخشش را انتخاب کنید.' : 'آماده تایید نهایی.'),
            (missing || unsettled) ? 'warn' : 'ok'
        );
    }

    function postLookup(payload) {
        setStatus('در حال بارگذاری...', 'info');
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) { setStatus(data.message || 'خطا', 'error'); return; }
                if (data.customer) {
                    if (nameInput) nameInput.value = data.customer.display_name || data.customer.name || nameInput.value;
                    if (phoneInput) phoneInput.value = data.customer.phone || phoneInput.value;
                    if (customerIdInput) customerIdInput.value = data.customer.id || '';
                }
                render(data.items || []);
            })
            .catch(function () { setStatus('خطا در ارتباط.', 'error'); });
    }

    function loadByCustomer() {
        var id = customerIdInput ? customerIdInput.value : '';
        var phone = phoneInput ? phoneInput.value.trim() : '';
        if (!id && !phone) {
            setStatus('ابتدا مشتری را از لیست نام انتخاب کنید.', 'error');
            return;
        }
        postLookup({ customer_id: id || 0, phone: phone });
    }

    function lookupTickets() {
        var tickets = document.getElementById('ticket-input').value.trim();
        if (!tickets) { setStatus('شماره قبض را وارد کنید.', 'error'); return; }
        postLookup({ tickets: tickets });
    }

    if (nameInput) {
        nameInput.addEventListener('input', function () {
            if (customerIdInput) customerIdInput.value = '';
            searchNames(false);
        });
        nameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchNames(true); }
        });
    }
    document.addEventListener('click', function (e) {
        if (pickList && !pickList.contains(e.target) && e.target !== nameInput) clearPick();
    });

    document.getElementById('load-customer-tickets-btn').addEventListener('click', loadByCustomer);
    document.getElementById('lookup-tickets-btn').addEventListener('click', lookupTickets);
    document.getElementById('ticket-input').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); lookupTickets(); }
    });

    document.getElementById('group-delivery-form').addEventListener('submit', function (e) {
        var checked = body.querySelectorAll('input[name="ticket_ids[]"]:checked:not([disabled])');
        if (!checked.length) {
            e.preventDefault();
            setStatus('حداقل یک قبض باز را تیک بزنید.', 'error');
            return;
        }
        var modeEl = document.querySelector('select[name="settlement_mode"]');
        var mode = modeEl ? modeEl.value : '';
        var unsettled = false;
        checked.forEach(function (cb) {
            var row = cb.closest('tr');
            if (row && row.querySelector('.pill-off') && row.textContent.indexOf('مانده‌دار') !== -1) unsettled = true;
        });
        if (unsettled && !mode) {
            e.preventDefault();
            setStatus('برای قبض‌های مانده‌دار، نسیه یا بخشش را انتخاب کنید.', 'error');
        }
    });
})();
</script>
@endpush
