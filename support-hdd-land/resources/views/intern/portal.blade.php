@extends('layouts.app')
@section('title', 'پرتال کارآموز | '.shop_name())
@section('page_title', 'پرتال کارآموز')
@section('window_title', 'خدمات شرکت و دفتر روز')

@section('content')
<div class="intern-portal daybook">
    <section class="panel daybook-hero">
        <div>
            <p class="daybook-eyebrow">پرتال کارآموز — {{ shop_name() }}</p>
            <h2 style="margin:0;">سلام {{ $user->name }}</h2>
            <p class="lead" style="margin:4px 0 0;">
                @if($intern)
                    دوره: {{ jalali_date($intern->start_date) }} تا {{ jalali_date($intern->end_date) }}
                    @if($intern->department) · {{ $intern->department }}@endif
                @else
                    دسته را بزنید؛ برای تعمیر/قبض جستجو کنید و کار را سریع بنویسید.
                @endif
            </p>
        </div>
        <div class="daybook-hero-actions">
            @if($canLog)
                <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">دفتر روز کامل</a>
            @endif
            <a class="btn btn-ghost" href="{{ route('profile.edit') }}">پروفایل</a>
        </div>
    </section>

    <section class="panel" style="margin-bottom:12px;">
        <div class="daybook-stats">
            <div><span>امروز</span><strong>{{ jalali_date($date) }}</strong></div>
            <div><span>ثبت امروز</span><strong>{{ $summary['count'] }}</strong></div>
            @if($showQuantity)
                <div><span>تعداد</span><strong>{{ $summary['quantity'] }}</strong></div>
            @endif
            <div><span>با قبض</span><strong>{{ $summary['with_ticket'] }}</strong></div>
            <div><span>خدمات فعال</span><strong>{{ $categories->count() }}</strong></div>
        </div>
    </section>

    @if($canLog)
        <section class="panel daybook-compose" id="daybook-compose" data-tickets-url="{{ $ticketSearchUrl }}">
            <h3 style="margin-top:0;">ثبت خدمت انجام‌شده</h3>
            <p class="muted" style="margin-top:0;">برای «تعمیر قطعات» و دسته‌های مرتبط با قبض، لیست جستجو باز می‌شود.</p>
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('intern.log') }}" class="daybook-form" id="daybook-form">
                @csrf
                <input type="hidden" name="reception_id" id="daybook-reception-id" value="{{ old('reception_id') }}">
                <div class="daybook-cats" id="intern-cats">
                    @forelse($categories as $cat)
                        <label class="daybook-cat">
                            <input type="radio" name="daily_log_category_id" value="{{ $cat->id }}"
                                   data-ask-qty="{{ $cat->ask_quantity ? '1' : '0' }}"
                                   data-need-ticket="{{ $cat->needsReceipt() ? '1' : '0' }}"
                                   data-name="{{ $cat->name }}"
                                   @checked((string) old('daily_log_category_id') === (string) $cat->id)
                                   required>
                            <span class="daybook-cat-mark">{{ $cat->mark }}</span>
                            <span>
                                <strong>{{ $cat->name }}</strong>
                                @if($cat->hint)<small>{{ $cat->hint }}</small>@endif
                                @if($cat->needsReceipt())<small class="daybook-ticket-badge">+ جستجوی قبض</small>@endif
                            </span>
                        </label>
                    @empty
                        <p class="lead">هنوز خدمتی تعریف نشده. از مدیر بخواهید در «تنظیمات دفتر روز» دسته اضافه کند.</p>
                    @endforelse
                </div>

                @if($categories->isNotEmpty())
                    <div class="daybook-ticket-panel hidden" id="daybook-ticket-panel">
                        <div class="daybook-ticket-head">
                            <strong>جستجوی قبض</strong>
                            <span class="muted">شماره قبض، سریال، نام یا موبایل مشتری</span>
                        </div>
                        <div class="daybook-ticket-search">
                            <input type="search" id="daybook-ticket-q" placeholder="مثلاً SH-… یا نام مشتری" autocomplete="off" dir="rtl">
                            <button type="button" class="btn btn-secondary btn-sm" id="daybook-ticket-search-btn">جستجو</button>
                        </div>
                        <div id="daybook-ticket-selected" class="daybook-ticket-selected hidden"></div>
                        <div id="daybook-ticket-list" class="daybook-ticket-list"></div>
                    </div>

                    <div class="daybook-hints" id="daybook-hints">
                        <span class="muted">میانبر کار:</span>
                        @foreach($workHints as $hint)
                            <button type="button" class="daybook-hint-chip" data-hint="{{ $hint }}">{{ $hint }}</button>
                        @endforeach
                    </div>

                    <div class="accept-row accept-row-2" style="margin-top:10px;">
                        @if($showQuantity)
                            <div id="intern-qty-wrap" class="hidden">
                                <label>تعداد</label>
                                <input type="number" name="quantity" min="0" max="9999" value="{{ old('quantity', 1) }}">
                            </div>
                        @endif
                        <div>
                            <label>مدت (دقیقه — اختیاری)</label>
                            <input type="number" name="minutes" min="0" max="1440" value="{{ old('minutes') }}" placeholder="مثلاً ۳۰">
                        </div>
                        <div style="grid-column:1/-1">
                            <label>توضیح {{ $requireNote ? '(الزامی)' : '(اختیاری)' }}</label>
                            <textarea name="body" id="daybook-body" rows="3" placeholder="چه کاری روی این قبض / خدمت انجام شد؟" {{ $requireNote ? 'required' : '' }}>{{ old('body') }}</textarea>
                        </div>
                    </div>
                    <div class="actions" style="margin-top:8px;">
                        <button class="btn btn-primary" type="submit">ثبت در دفتر روز</button>
                    </div>
                @endif
            </form>
        </section>
    @else
        <section class="panel">
            <p class="lead">دسترسی دفتر روزانه برای شما فعال نیست. از مدیر بخواهید در کارتابل کارآموز، دسترسی «دفتر روزانه» را روشن کند.</p>
        </section>
    @endif

    <section class="panel" style="margin-top:12px;">
        <h3 style="margin-top:0;">ثبت‌های امروز</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>خدمت</th>
                    <th>قبض</th>
                    <th>توضیح</th>
                    <th>تعداد</th>
                    <th>زمان</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->displayTitle() }}</td>
                        <td>
                            @if($entry->ticketLabel())
                                {{ $entry->ticketLabel() }}
                                @if($entry->reception?->customer)
                                    <div class="muted" style="font-size:10px;">{{ $entry->reception->customer->name }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $entry->body ?: '—' }}</td>
                        <td>{{ $entry->quantity ?? '—' }}</td>
                        <td>{{ jalali_like($entry->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">هنوز چیزی برای امروز ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var compose = document.getElementById('daybook-compose');
    if (!compose) return;
    var url = compose.getAttribute('data-tickets-url');
    var cats = document.getElementById('intern-cats');
    var body = document.getElementById('daybook-body');
    var panel = document.getElementById('daybook-ticket-panel');
    var qtyWrap = document.getElementById('intern-qty-wrap');
    var receptionId = document.getElementById('daybook-reception-id');
    var selectedBox = document.getElementById('daybook-ticket-selected');
    var listBox = document.getElementById('daybook-ticket-list');
    var qInput = document.getElementById('daybook-ticket-q');
    var searchBtn = document.getElementById('daybook-ticket-search-btn');
    var selectedTicket = null;
    var timer = null;

    function selectedCat() {
        return cats ? cats.querySelector('input[name="daily_log_category_id"]:checked') : null;
    }

    function syncCatUi() {
        var input = selectedCat();
        document.querySelectorAll('.daybook-cat').forEach(function (el) {
            el.classList.toggle('is-on', input && el.contains(input));
        });
        var need = input && input.getAttribute('data-need-ticket') === '1';
        var askQty = input && input.getAttribute('data-ask-qty') === '1';
        if (panel) panel.classList.toggle('hidden', !need);
        if (qtyWrap) qtyWrap.classList.toggle('hidden', !askQty);
        if (!need) {
            clearTicket();
        } else if (qInput && !qInput.value) {
            qInput.focus();
        }
    }

    function clearTicket() {
        selectedTicket = null;
        if (receptionId) receptionId.value = '';
        if (selectedBox) {
            selectedBox.classList.add('hidden');
            selectedBox.innerHTML = '';
        }
        if (listBox) listBox.innerHTML = '';
    }

    function pickTicket(t) {
        selectedTicket = t;
        if (receptionId) receptionId.value = t.id;
        if (selectedBox) {
            selectedBox.classList.remove('hidden');
            selectedBox.innerHTML = '<div class="daybook-ticket-chip">' +
                '<strong>' + (t.ticket_no || t.receipt_no || '') + '</strong> · ' +
                (t.customer || '—') +
                (t.serial ? ' · <span dir="ltr">' + t.serial + '</span>' : '') +
                ' · ' + (t.status_label || '') +
                ' <button type="button" class="btn btn-ghost btn-sm" id="daybook-ticket-clear">حذف</button></div>';
            var clr = document.getElementById('daybook-ticket-clear');
            if (clr) clr.addEventListener('click', clearTicket);
        }
        if (listBox) listBox.innerHTML = '';
        if (body) body.focus();
    }

    function renderList(tickets) {
        if (!listBox) return;
        if (!tickets.length) {
            listBox.innerHTML = '<p class="muted" style="margin:8px 0 0;">قبضی پیدا نشد.</p>';
            return;
        }
        listBox.innerHTML = tickets.map(function (t) {
            return '<button type="button" class="daybook-ticket-row" data-id="' + t.id + '">' +
                '<strong>' + (t.ticket_no || t.receipt_no || '') + '</strong>' +
                '<span>' + (t.customer || '—') + (t.phone ? ' · ' + t.phone : '') + '</span>' +
                '<span class="muted">' + (t.product || '') + (t.serial ? ' · ' + t.serial : '') + ' · ' + (t.status_label || '') + '</span>' +
                '</button>';
        }).join('');
        listBox.querySelectorAll('.daybook-ticket-row').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                var t = tickets.find(function (x) { return String(x.id) === String(id); });
                if (t) pickTicket(t);
            });
        });
    }

    function searchTickets() {
        if (!url || !qInput) return;
        var q = (qInput.value || '').trim();
        if (q.length < 2) {
            listBox.innerHTML = '<p class="muted" style="margin:8px 0 0;">حداقل ۲ حرف وارد کنید.</p>';
            return;
        }
        listBox.innerHTML = '<p class="muted" style="margin:8px 0 0;">در حال جستجو…</p>';
        fetch(url + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            renderList((data && data.tickets) || []);
        }).catch(function () {
            listBox.innerHTML = '<p class="muted" style="margin:8px 0 0;">خطا در جستجو.</p>';
        });
    }

    if (cats) cats.addEventListener('change', syncCatUi);
    if (searchBtn) searchBtn.addEventListener('click', searchTickets);
    if (qInput) {
        qInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchTickets(); }
        });
        qInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(searchTickets, 350);
        });
    }
    document.querySelectorAll('.daybook-hint-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (!body) return;
            var h = chip.getAttribute('data-hint') || '';
            body.value = body.value ? (body.value.replace(/\s+$/, '') + ' · ' + h) : h;
            body.focus();
        });
    });

    var form = document.getElementById('daybook-form');
    if (form) form.addEventListener('submit', function (e) {
        var input = selectedCat();
        if (input && input.getAttribute('data-need-ticket') === '1' && !(receptionId && receptionId.value)) {
            e.preventDefault();
            alert('برای این دسته باید قبض را جستجو و انتخاب کنید.');
            if (panel) panel.classList.remove('hidden');
            if (qInput) qInput.focus();
        }
    });

    syncCatUi();
})();
</script>
@endpush
