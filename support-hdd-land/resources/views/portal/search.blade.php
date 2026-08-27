@extends('layouts.portal')
@section('title', 'جستجوی قبض | '.shop_name())

@section('content')
<header class="p-top">
    <div class="p-brand-mini">
        <a class="p-icon-btn" href="{{ route('portal.home') }}" title="بازگشت" style="text-decoration:none;display:grid;place-items:center;">→</a>
        <div>
            <div class="p-hello">جستجوی قبض</div>
            <div class="p-sub">شماره قبض، سریال، برند یا مدل</div>
        </div>
    </div>
</header>

<div class="portal-shell" style="padding-top:10px;">
<form class="p-search-bar" method="GET" action="{{ route('portal.search') }}">
    @include('partials.receipt-search-input', [
        'name' => 'q',
        'value' => $q,
        'autofocus' => true,
        'placeholder' => '1000',
        'hint' => 'پیش‌فرض T-20N — ادامه کد را بزنید؛ برای سریال همان کادر را کامل بنویسید.',
        'allowFree' => true,
        'barcode' => false,
    ])
    <button type="submit" class="p-btn primary">جستجو</button>
</form>
@push('scripts')
<script>
(function () {
    function convertDigits(v) {
        return String(v || '').replace(/[۰-۹٠-٩]/g, function (c) {
            var map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
            return map[c] || c;
        });
    }
    function sync(wrap) {
        var prefix = wrap.getAttribute('data-prefix') || 'T-20N';
        var allowFree = wrap.getAttribute('data-allow-free') !== '0';
        var suffix = wrap.querySelector('[data-receipt-suffix]');
        var hidden = wrap.querySelector('[data-receipt-full]');
        if (!suffix || !hidden) return;
        var raw = convertDigits(suffix.value || '').trim();
        if (!raw) { hidden.value = ''; return; }
        if (/^t-20n/i.test(raw)) {
            hidden.value = 'T-20N' + raw.slice(5).replace(/\s+/g, '');
            suffix.value = raw.slice(5).replace(/\s+/g, '');
            return;
        }
        if (/^\d+$/.test(raw)) { hidden.value = prefix + raw; return; }
        hidden.value = allowFree ? raw : (prefix + raw);
    }
    document.querySelectorAll('[data-receipt-prefix-wrap]').forEach(function (wrap) {
        var suffix = wrap.querySelector('[data-receipt-suffix]');
        if (!suffix) return;
        ['input', 'change', 'blur'].forEach(function (ev) {
            suffix.addEventListener(ev, function () { sync(wrap); });
        });
        var form = wrap.closest('form');
        if (form) form.addEventListener('submit', function () { sync(wrap); });
        sync(wrap);
    });
})();
</script>
@endpush

<div class="p-ticket-list">
    @if($q === '')
        <div class="p-empty">عبارت جستجو را وارد کنید.</div>
    @else
        @forelse($tickets as $t)
            @include('portal._ticket-card', ['ticket' => $t, 'highlightPay' => $t->status === 'ready'])
        @empty
            <div class="p-empty">نتیجه‌ای برای «{{ $q }}» پیدا نشد.</div>
        @endforelse
    @endif
</div>
</div>
@endsection
