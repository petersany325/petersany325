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
    $ticketJson = ($openTickets ?? collect())->values()->toJson(JSON_UNESCAPED_UNICODE);
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
            طبق حسابداری دوطرفه: وقتی مشتری بدهکار پول می‌دهد،
            <strong>صندوق/بانک بدهکار</strong> و <strong>حساب دریافتنی (۱۲۱۰) بستانکار</strong> می‌شود تا مانده همان شخص کم شود.
            اگر قبض انتخاب شود، پرداخت روی قبض هم ثبت می‌گردد.
        </p>
    </section>

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    @if($mode === 'receipt')
        <section class="acc-panel">
            <header class="acc-panel-head"><h3>ثبت دریافت از بدهکار</h3></header>
            <form method="POST" action="{{ route('accounting.manual.store') }}" id="acc-receipt-form">
                @csrf
                <input type="hidden" name="mode" value="receipt">

                <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                    <div>
                        <label>مشتری بدهکار *</label>
                        <select name="customer_id" id="acc-customer" required>
                            <option value="">— انتخاب مشتری —</option>
                            @foreach($debtors as $d)
                                <option value="{{ $d['id'] }}"
                                    data-balance="{{ $d['balance'] }}"
                                    @selected((string) old('customer_id', $preCustomerId) === (string) $d['id'])>
                                    {{ $d['name'] }}
                                    @if($d['phone']) — {{ $d['phone'] }} @endif
                                    (مانده {{ number_format($d['balance']) }})
                                </option>
                            @endforeach
                            @if($preCustomer && collect($debtors)->where('id', $preCustomer->id)->isEmpty())
                                <option value="{{ $preCustomer->id }}" selected data-balance="{{ $preBalance }}">
                                    {{ $preCustomer->name }} @if($preCustomer->phone) — {{ $preCustomer->phone }} @endif
                                    (مانده {{ number_format($preBalance) }})
                                </option>
                            @endif
                        </select>
                        <p class="muted" id="acc-balance-hint" style="margin:6px 0 0;font-size:11.5px;">
                            @if($preBalance)
                                مانده دفتر ۱۲۱۰ این مشتری: {{ number_format($preBalance) }} تومان
                            @endif
                        </p>
                    </div>
                    <div>
                        <label>قبض مرتبط (اختیاری)</label>
                        <select name="reception_id" id="acc-reception">
                            <option value="">— بدون قبض (فقط دفتر مشتری) —</option>
                        </select>
                        <p class="muted" style="margin:6px 0 0;font-size:11.5px;">با انتخاب قبض، مانده همان قبض هم تسویه می‌شود.</p>
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
<script>
(function () {
    const tickets = {!! $ticketJson !!};
    const customerEl = document.getElementById('acc-customer');
    const receptionEl = document.getElementById('acc-reception');
    const amountEl = document.getElementById('acc-amount');
    const hintEl = document.getElementById('acc-balance-hint');
    const preReception = @json(old('reception_id', $preReceptionId));

    function fmt(n) {
        try { return Number(n).toLocaleString('en-US'); } catch (e) { return String(n); }
    }

    function refreshTickets() {
        const cid = customerEl.value;
        const bal = customerEl.selectedOptions[0]?.getAttribute('data-balance');
        if (hintEl) {
            hintEl.textContent = bal
                ? ('مانده دفتر ۱۲۱۰ این مشتری: ' + fmt(bal) + ' تومان')
                : '';
        }
        const keep = receptionEl.value || preReception || '';
        receptionEl.innerHTML = '<option value="">— بدون قبض (فقط دفتر مشتری) —</option>';
        tickets.filter(t => String(t.customer_id) === String(cid)).forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.label;
            opt.dataset.remaining = t.remaining;
            if (String(keep) === String(t.id)) opt.selected = true;
            receptionEl.appendChild(opt);
        });
        maybeFillAmount();
    }

    function maybeFillAmount() {
        const opt = receptionEl.selectedOptions[0];
        const rem = opt && opt.dataset.remaining ? parseInt(opt.dataset.remaining, 10) : 0;
        if (rem > 0 && amountEl && !amountEl.value) {
            amountEl.value = rem;
        }
    }

    customerEl?.addEventListener('change', refreshTickets);
    receptionEl?.addEventListener('change', maybeFillAmount);
    refreshTickets();
})();
</script>
@endif
@endsection
