@extends('layouts.app')

@section('title', 'ارجاع قبض به همکار | '.shop_name())
@section('page_title', 'ارجاع قبض به همکار شبکه')
@section('window_title', 'ارسال قبض کامل پس از انتخاب همکار')

@section('content')
<section class="panel" style="margin-bottom:12px;background:#f3f7ff;border-color:#b7c8e8;">
    <strong>همکار انتخاب‌شده</strong>
    <div style="margin-top:8px;">
        <div style="font-size:18px;font-weight:800;">{{ $partner->displayName() }}</div>
        @if($partner->address)
            <div style="margin-top:4px;">{{ $partner->address }}</div>
        @endif
        <div class="muted" style="margin-top:4px;">
            @if($partner->domain)<span dir="ltr">{{ $partner->domain }}</span> · @endif
            @if($partner->phone)<span dir="ltr">{{ $partner->phone }}</span>@endif
        </div>
    </div>
    <p class="muted" style="margin:10px 0 0;">قبض انتخابی با همان شماره قبض مبدأ برای این همکار ارسال می‌شود. در مقصد قبض جدید ساخته می‌شود و تا تأیید منشی در وضعیت «منتظر قطعه» می‌ماند.</p>
</section>

<section class="panel" style="margin-bottom:12px;">
    <form method="GET" action="{{ route('partners.refer-form', $partner) }}" class="accept-row accept-row-3" style="align-items:end;" id="receipt-search-form">
        <div style="grid-column:1 / span 2;">
            <label>سرچ قبض</label>
            <input type="text" name="q" id="receipt-search-q" value="{{ $q }}" placeholder="شماره قبض / تیکت / سریال / اسم مشتری / موبایل / مدل" autofocus>
            <div class="muted" style="font-size:11px;margin-top:4px;">با شماره قبض، تیکت، سریال، نام مشتری یا موبایل جستجو کنید.</div>
        </div>
        <div class="actions" style="margin:0;flex-wrap:wrap;">
            <button class="btn btn-primary" type="submit">جستجو</button>
            <a class="btn btn-ghost" href="{{ route('partners.refer-form', $partner) }}">پاک</a>
        </div>
    </form>
</section>

<section class="panel">
    <form method="POST" action="{{ route('partners.refer-send', $partner) }}" id="partner-refer-send">
        @csrf
        <div class="accept-row accept-row-2" style="align-items:end;">
            <div style="grid-column:1/-1;">
                <label>انتخاب قبض برای ارسال *</label>
                <select name="reception_id" id="reception-pick" required>
                    <option value="">— قبض را انتخاب کنید —</option>
                    @foreach($receptions as $r)
                        @php
                            $label = trim(
                                ($r->receipt_no ?: '').
                                ($r->ticket_no ? ' / '.$r->ticket_no : '').
                                ' — '.($r->customer?->name ?: 'بدون نام').
                                ($r->product_name ? ' — '.$r->product_name : '').
                                ($r->serial_number ? ' ('.$r->serial_number.')' : '')
                            );
                            $hay = mb_strtolower(implode(' ', array_filter([
                                $r->receipt_no, $r->ticket_no, $r->serial_number, $r->product_name, $r->brand, $r->model,
                                $r->customer?->name, $r->customer?->phone, $r->customer?->alias,
                            ])));
                        @endphp
                        <option value="{{ $r->id }}"
                                data-search="{{ $hay }}"
                                @selected((string) old('reception_id') === (string) $r->id)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <div class="muted" id="receipt-search-hint" style="font-size:11px;margin-top:4px;">
                    @if($q !== '')
                        {{ $receptions->count() }} نتیجه برای «{{ $q }}»
                    @else
                        {{ $receptions->count() }} قبض اخیر — برای یافتن قبض قدیمی‌تر از سرچ استفاده کنید.
                    @endif
                </div>
            </div>
            <div style="grid-column:1/-1;">
                <label>یادداشت برای همکار مقصد</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="مثلاً قطعه همراه است / نیاز به تخصص">
            </div>
        </div>
        @if($receptions->isEmpty())
            <p class="muted" style="margin-top:12px;">
                @if($q !== '')
                    قبضی با این سرچ پیدا نشد. عبارت را عوض کنید یا پاک کنید.
                @else
                    قبض بازی برای ارجاع نیست. اول در پذیرش یک قبض بسازید، سپس اینجا انتخاب کنید.
                @endif
            </p>
        @endif
        <div class="actions" style="margin-top:16px;">
            <button class="btn btn-primary" type="submit" @disabled($receptions->isEmpty())>ارسال ارجاع به این همکار</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">بازگشت به فهرست</a>
            <a class="btn btn-ghost" href="{{ route('partners.cartable', ['tab' => 'outbound']) }}">کارتابل ارجاع</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('receipt-search-q');
    var select = document.getElementById('reception-pick');
    var hint = document.getElementById('receipt-search-hint');
    if (!input || !select) return;

    var allOptions = Array.prototype.slice.call(select.querySelectorAll('option[data-search]'));
    var timer = null;

    function filterLocal() {
        var q = (input.value || '').trim().toLowerCase();
        var shown = 0;
        allOptions.forEach(function (opt) {
            var hay = (opt.getAttribute('data-search') || '') + ' ' + (opt.textContent || '');
            var ok = !q || hay.toLowerCase().indexOf(q) !== -1;
            opt.hidden = !ok;
            opt.disabled = !ok;
            if (ok) shown++;
        });
        if (hint) {
            if (q) {
                hint.textContent = shown + ' قبض در فهرست فعلی با «' + input.value.trim() + '» — Enter برای جستجوی کامل‌تر در دیتابیس';
            } else {
                hint.textContent = allOptions.length + ' قبض در فهرست — برای یافتن قبض قدیمی‌تر سرچ کنید و Enter بزنید';
            }
        }
        if (select.value) {
            var cur = select.options[select.selectedIndex];
            if (cur && cur.hidden) select.value = '';
        }
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(filterLocal, 120);
    });
    filterLocal();
})();
</script>
@endpush
