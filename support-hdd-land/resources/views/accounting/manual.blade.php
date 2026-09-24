@extends('layouts.app')
@section('title', 'سند دستی | '.shop_name())
@section('page_title', 'ثبت سند حسابداری')
@section('window_title', 'سند دستی')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'سند دستی',
    'accSub' => 'دریافت از بدهکار (کاهش حساب ۱۲۱۰) یا سند دوطرفه عمومی',
])

@php
    $mode = old('mode', $mode ?? 'receipt');
    $preDebtJson = json_encode($preDebt ?? [
        'ok' => true, 'count' => 0, 'tickets' => [], 'summary' => '', 'numbers' => [],
        'total_remaining' => 0, 'total_paid' => 0, 'customer' => null, 'ledger_balance' => null,
    ], JSON_UNESCAPED_UNICODE);
@endphp

<div class="acc-desk">
    <section class="acc-panel" style="margin-bottom:12px;">
        <div class="actions" style="flex-wrap:wrap;gap:8px;">
            <a class="btn {{ $mode === 'receipt' ? 'btn-primary' : 'btn-ghost' }}"
               href="{{ route('accounting.manual', array_filter(['mode' => 'receipt', 'customer_id' => $preCustomerId, 'reception_id' => $preReceptionId])) }}">
                دریافت از بدهکار
            </a>
            <a class="btn {{ $mode === 'general' ? 'btn-primary' : 'btn-ghost' }}"
               href="{{ route('accounting.manual', ['mode' => 'general']) }}">
                سند دوطرفه عمومی
            </a>
            <a class="btn btn-ghost" href="{{ route('accounting.receivables') }}">فهرست بدهکاران</a>
        </div>
        <p class="muted" style="margin:10px 0 0;font-size:12px;line-height:1.7;">
            مشتری یا شماره قبض را آنلاین جستجو کنید. قبض‌های مانده‌دار با شماره نشان داده می‌شوند؛
            بعد از تسویه کامل از لیست حذف می‌شوند و اگر بخشی پرداخت شده باشد،
            «پرداخت‌شده / مانده» تا صفر شدن نمایش داده می‌شود.
        </p>
    </section>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
            @if(session('journal_url'))
                — <a href="{{ session('journal_url') }}">مشاهده سند</a>
            @endif
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @if($mode === 'receipt')
        <section class="acc-panel" id="acc-receipt-desk"
                 data-customers-url="{{ $customerSuggestUrl }}"
                 data-tickets-url="{{ $debtTicketsUrl }}">
            <header class="acc-panel-head"><h3>ثبت دریافت از بدهکار</h3></header>
            <form method="POST" action="{{ route('accounting.manual.store') }}" id="acc-receipt-form">
                @csrf
                <input type="hidden" name="mode" value="receipt">
                <input type="hidden" name="customer_id" id="acc-customer-id"
                       value="{{ old('customer_id', $preCustomerId) }}" required>

                <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    <div style="position:relative;">
                        <label>جستجوی مشتری *</label>
                        <input type="search" id="acc-customer-q" autocomplete="off"
                               value="{{ old('customer_label', $preCustomer?->name) }}"
                               placeholder="نام یا موبایل مشتری…" required>
                        <div class="customer-pick-list" id="acc-customer-pick" hidden></div>
                        <p class="muted" id="acc-balance-hint" style="margin:6px 0 0;font-size:11.5px;">
                            @if($preBalance)
                                مانده دفتر ۱۲۱۰ این مشتری: {{ number_format($preBalance) }} تومان
                            @elseif($preCustomer)
                                مشتری انتخاب شد — در حال بارگذاری قبض‌های مانده‌دار…
                            @endif
                        </p>
                    </div>
                    <div>
                        <label>جستجوی قبض آنلاین</label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <input type="search" id="acc-ticket-q" autocomplete="off" dir="ltr"
                                   style="flex:1;min-width:140px;text-align:left;"
                                   placeholder="SH-… / شماره قبض / سریال">
                            <button type="button" class="btn btn-secondary" id="acc-ticket-search-btn">جستجو</button>
                        </div>
                        <p class="muted" style="margin:6px 0 0;font-size:11.5px;">مستقیم از دیتابیس؛ فقط قبض‌های مانده‌دار.</p>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label>قبض‌های مانده‌دار</label>
                        <div id="acc-ticket-summary" class="acc-debt-summary">
                            {{ $preDebt['summary'] ?? 'هنوز مشتری یا قبض انتخاب نشده.' }}
                        </div>
                        <select name="reception_id" id="acc-reception" style="margin-top:8px;">
                            <option value="">— بدون قبض (فقط دفتر مشتری) —</option>
                        </select>
                        <p class="muted" id="acc-ticket-detail" style="margin:6px 0 0;font-size:11.5px;"></p>
                    </div>
                    <div>
                        <label>مبلغ دریافتی (تومان) *</label>
                        <input type="number" name="amount" id="acc-amount" min="1" required
                               value="{{ old('amount') }}" dir="ltr">
                    </div>
                    <div>
                        <label>روش دریافت *</label>
                        <select name="method" required>
                            @foreach($methods as $key => $label)
                                @if($key === 'zarinpal') @continue @endif
                                <option value="{{ $key }}" @selected(old('method', 'cash') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>تاریخ سند *</label>
                        @include('partials.jalali-date', ['name' => 'entry_date', 'value' => old('entry_date', now())])
                    </div>
                    <div>
                        <label>یادداشت</label>
                        <input type="text" name="note" value="{{ old('note') }}" placeholder="مثلاً تسویه بخشی از بدهی">
                    </div>
                </div>

                <div class="acc-panel" style="margin-top:14px;padding:12px;background:#f8fafc;">
                    <strong style="display:block;margin-bottom:6px;">پیش‌نمایش سند</strong>
                    <p class="muted" style="margin:0;font-size:12.5px;line-height:1.8;">
                        بدهکار: صندوق / کارتخوان / کارت‌به‌کارت (به اندازه مبلغ)<br>
                        بستانکار: حساب‌های دریافتنی مشتریان ۱۲۱۰ (کاهش بدهی همان شخص)
                    </p>
                </div>

                <div class="actions" style="margin-top:12px;">
                    <button class="btn btn-primary" type="submit">ثبت دریافت و کاهش بدهی</button>
                </div>
            </form>
        </section>
    @else
        <section class="acc-panel">
            <header class="acc-panel-head"><h3>سند دوطرفه عمومی</h3></header>
            <form method="POST" action="{{ route('accounting.manual.store') }}" id="acc-manual-form">
                @csrf
                <input type="hidden" name="mode" value="general">
                <div class="form-grid" style="grid-template-columns:2fr 1fr 1fr;">
                    <div>
                        <label>شرح سند *</label>
                        <input type="text" name="description" value="{{ old('description') }}" required
                               placeholder="مثلاً اصلاح حساب / هزینه متفرقه">
                    </div>
                    <div>
                        <label>تاریخ *</label>
                        @include('partials.jalali-date', ['name' => 'entry_date', 'value' => old('entry_date', now())])
                    </div>
                    <div>
                        <label>مشتری (الزامی اگر ردیف ۱۲۱۰ دارید)</label>
                        <select name="customer_id">
                            <option value="">—</option>
                            @foreach($debtors as $d)
                                <option value="{{ $d['id'] }}" @selected((string) old('customer_id') === (string) $d['id'])>
                                    {{ $d['name'] }} ({{ number_format($d['balance']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-wrap" style="margin-top:12px;">
                    <table class="compact-table acc-table" id="acc-manual-lines">
                        <thead>
                        <tr><th>حساب</th><th>بدهکار</th><th>بستانکار</th><th>یادداشت</th></tr>
                        </thead>
                        <tbody>
                        @for($i = 0; $i < 4; $i++)
                            <tr>
                                <td>
                                    <select name="lines[{{ $i }}][account_id]" @if($i < 2) required @endif>
                                        <option value="">—</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}" @selected((string) old("lines.$i.account_id") === (string) $acc->id)>
                                                {{ $acc->code }} — {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" min="0" name="lines[{{ $i }}][debit]" value="{{ old("lines.$i.debit", 0) }}"></td>
                                <td><input type="number" min="0" name="lines[{{ $i }}][credit]" value="{{ old("lines.$i.credit", 0) }}"></td>
                                <td><input type="text" name="lines[{{ $i }}][memo]" value="{{ old("lines.$i.memo") }}"></td>
                            </tr>
                        @endfor
                        </tbody>
                    </table>
                </div>
                <p class="muted" style="margin-top:8px;font-size:11.5px;">جمع بدهکار باید دقیقاً برابر جمع بستانکار باشد.</p>
                <div class="actions" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit">ثبت سند</button>
                </div>
            </form>
        </section>
    @endif
</div>

@if($mode === 'receipt')
@push('scripts')
<script>
(function () {
    var desk = document.getElementById('acc-receipt-desk');
    if (!desk) return;
    var customersUrl = desk.getAttribute('data-customers-url');
    var ticketsUrl = desk.getAttribute('data-tickets-url');
    var customerIdEl = document.getElementById('acc-customer-id');
    var customerQ = document.getElementById('acc-customer-q');
    var pickList = document.getElementById('acc-customer-pick');
    var ticketQ = document.getElementById('acc-ticket-q');
    var ticketSearchBtn = document.getElementById('acc-ticket-search-btn');
    var receptionEl = document.getElementById('acc-reception');
    var summaryEl = document.getElementById('acc-ticket-summary');
    var detailEl = document.getElementById('acc-ticket-detail');
    var amountEl = document.getElementById('acc-amount');
    var hintEl = document.getElementById('acc-balance-hint');
    var preReception = @json(old('reception_id', $preReceptionId));
    var initialDebt = {!! $preDebtJson !!};
    var ticketsCache = [];
    var nameTimer = null;
    var ticketTimer = null;
    var nameSeq = 0;

    function fmt(n) {
        try { return Number(n || 0).toLocaleString('en-US'); } catch (e) { return String(n); }
    }

    function clearPick() {
        if (!pickList) return;
        pickList.innerHTML = '';
        pickList.hidden = true;
    }

    function setHint(text) {
        if (hintEl) hintEl.textContent = text || '';
    }

    function setSummary(text) {
        if (summaryEl) summaryEl.textContent = text || '';
    }

    function applyDebtPayload(data, preferReceptionId) {
        data = data || {};
        ticketsCache = data.tickets || [];
        setSummary(data.summary || 'قبض مانده‌داری نیست.');
        if (data.customer && data.customer.id) {
            customerIdEl.value = data.customer.id;
            if (customerQ && (!customerQ.value || data.customer.name)) {
                customerQ.value = data.customer.name + (data.customer.phone ? (' — ' + data.customer.phone) : '');
            }
        }
        if (typeof data.ledger_balance === 'number' && data.ledger_balance !== null) {
            setHint('مانده دفتر ۱۲۱۰ این مشتری: ' + fmt(data.ledger_balance) + ' تومان');
        } else if (data.customer) {
            setHint('مشتری انتخاب شد — ' + (data.count || 0) + ' قبض مانده‌دار');
        }

        var keep = preferReceptionId || data.matched_reception_id || receptionEl.value || preReception || '';
        receptionEl.innerHTML = '<option value="">— بدون قبض (فقط دفتر مشتری) —</option>';
        ticketsCache.forEach(function (t) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.label;
            opt.dataset.remaining = t.remaining;
            opt.dataset.paid = t.paid;
            opt.dataset.total = t.total;
            opt.dataset.ticket = t.ticket_no || t.receipt_no || '';
            opt.dataset.customerId = t.customer_id || '';
            if (String(keep) === String(t.id)) opt.selected = true;
            receptionEl.appendChild(opt);
        });
        updateTicketDetail();
        maybeFillAmount(true);
    }

    function updateTicketDetail() {
        var opt = receptionEl.selectedOptions[0];
        if (!detailEl) return;
        if (!opt || !opt.value) {
            detailEl.textContent = ticketsCache.length
                ? 'یک قبض را انتخاب کنید تا مانده همان قبض تسویه شود؛ بعد از تسویه کامل از لیست حذف می‌شود.'
                : '';
            return;
        }
        var paid = parseInt(opt.dataset.paid || '0', 10);
        var rem = parseInt(opt.dataset.remaining || '0', 10);
        var total = parseInt(opt.dataset.total || '0', 10);
        var bits = ['قبض ' + (opt.dataset.ticket || opt.value)];
        if (paid > 0) bits.push('قبلاً پرداخت‌شده ' + fmt(paid) + ' تومان');
        bits.push('مانده فعلی ' + fmt(rem) + ' از ' + fmt(total) + ' تومان');
        detailEl.textContent = bits.join(' · ');
    }

    function maybeFillAmount(force) {
        var opt = receptionEl.selectedOptions[0];
        var rem = opt && opt.dataset.remaining ? parseInt(opt.dataset.remaining, 10) : 0;
        if (rem > 0 && amountEl && (force || !amountEl.value)) {
            amountEl.value = rem;
        }
    }

    function loadTicketsForCustomer(customerId, preferReceptionId) {
        if (!ticketsUrl || !customerId) return;
        setSummary('در حال بارگذاری قبض‌های مانده‌دار…');
        fetch(ticketsUrl + '?customer_id=' + encodeURIComponent(customerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            applyDebtPayload(data, preferReceptionId);
        }).catch(function () {
            setSummary('خطا در بارگذاری قبض‌ها.');
        });
    }

    function searchTicketsOnline() {
        if (!ticketsUrl || !ticketQ) return;
        var q = (ticketQ.value || '').trim();
        if (q.length < 2) {
            setSummary('حداقل ۲ حرف از شماره قبض یا نام وارد کنید.');
            return;
        }
        setSummary('در حال جستجوی آنلاین قبض…');
        fetch(ticketsUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && data.message && !(data.tickets && data.tickets.length)) {
                setSummary(data.message);
            }
            applyDebtPayload(data, data && data.matched_reception_id);
        }).catch(function () {
            setSummary('خطا در جستجوی قبض.');
        });
    }

    function selectCustomer(c) {
        if (!c) return;
        customerIdEl.value = c.id || '';
        customerQ.value = (c.display_name || c.name || '') + (c.phone ? (' — ' + c.phone) : '');
        clearPick();
        loadTicketsForCustomer(c.id, preReception);
    }

    function renderPick(list, q) {
        if (!pickList) return;
        pickList.innerHTML = '';
        if (!list || !list.length) {
            pickList.hidden = false;
            var empty = document.createElement('div');
            empty.className = 'customer-pick-empty';
            empty.textContent = q ? 'مشتری پیدا نشد.' : 'نام را بنویسید.';
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
            meta.textContent = bits.join(' · ') || '—';
            btn.appendChild(name);
            btn.appendChild(meta);
            btn.addEventListener('click', function () { selectCustomer(c); });
            pickList.appendChild(btn);
        });
        pickList.hidden = false;
    }

    function searchCustomers() {
        if (!customersUrl || !customerQ) return;
        var q = (customerQ.value || '').trim();
        if (q.length < 1) { clearPick(); return; }
        var seq = ++nameSeq;
        fetch(customersUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (seq !== nameSeq) return;
            renderPick((data && data.customers) || [], q);
        }).catch(function () {});
    }

    customerQ?.addEventListener('input', function () {
        customerIdEl.value = '';
        if (nameTimer) clearTimeout(nameTimer);
        nameTimer = setTimeout(searchCustomers, 220);
    });
    customerQ?.addEventListener('focus', function () {
        if ((customerQ.value || '').trim().length >= 1) searchCustomers();
    });
    document.addEventListener('click', function (e) {
        if (pickList && !pickList.contains(e.target) && e.target !== customerQ) clearPick();
    });

    ticketSearchBtn?.addEventListener('click', searchTicketsOnline);
    ticketQ?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); searchTicketsOnline(); }
    });
    ticketQ?.addEventListener('input', function () {
        if (ticketTimer) clearTimeout(ticketTimer);
        ticketTimer = setTimeout(function () {
            if ((ticketQ.value || '').trim().length >= 2) searchTicketsOnline();
        }, 350);
    });

    receptionEl?.addEventListener('change', function () {
        var opt = receptionEl.selectedOptions[0];
        if (opt && opt.dataset.customerId) {
            var cid = String(opt.dataset.customerId);
            if (String(customerIdEl.value) !== cid) {
                customerIdEl.value = cid;
                loadTicketsForCustomer(cid, opt.value);
                return;
            }
        }
        updateTicketDetail();
        maybeFillAmount(true);
    });

    document.getElementById('acc-receipt-form')?.addEventListener('submit', function (e) {
        if (!customerIdEl.value) {
            e.preventDefault();
            alert('ابتدا مشتری را از جستجو انتخاب کنید.');
            customerQ?.focus();
        }
    });

    // Initial: preload from server when customer_id is in URL / old input
    if (initialDebt && (initialDebt.tickets || []).length) {
        applyDebtPayload(initialDebt, preReception);
    } else if (customerIdEl.value) {
        loadTicketsForCustomer(customerIdEl.value, preReception);
    }
})();
</script>
@endpush
@endif
@endsection
