@extends('layouts.app')
@section('title', 'دفتر روز | '.shop_name())
@section('page_title', 'دفتر روز')
@section('window_title', 'ثبت کار و رویدادهای روزانه')

@section('content')
@php
    $dateStr = $date->toDateString();
    $prev = $date->copy()->subDay()->toDateString();
    $next = $date->copy()->addDay()->toDateString();
    $today = now('Asia/Tehran')->toDateString();
    $roleLabel = [
        'admin' => 'مدیر',
        'employee' => 'کارمند',
        'intern' => 'کارآموز',
        'technician' => 'تعمیرکار',
        'accountant' => 'حسابدار',
    ];
@endphp
<div class="daybook">
    <section class="daybook-hero panel">
        <div>
            <p class="daybook-eyebrow">دفتر کار روزانه — کارمند و کارآموز</p>
            <h2>دفتر روز</h2>
            <p class="lead" style="margin:4px 0 0;">دسته را بزنید، برای تعمیر/قبض جستجو کنید، کار را سریع بنویسید.</p>
        </div>
        <div class="daybook-hero-actions">
            @if($canManage)
                <a class="btn btn-secondary" href="{{ route('daily-logs.report') }}">گزارش همه</a>
            @endif
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات</a>
            @endif
        </div>
    </section>

    <section class="daybook-toolbar panel">
        <form method="GET" class="daybook-filters" action="{{ route('daily-logs.index') }}">
            <div class="daybook-nav">
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $prev, 'user_id' => $employee->id]) }}">روز قبل</a>
                @include('partials.jalali-date', ['name' => 'date', 'value' => $dateStr, 'attrs' => 'onchange="this.form.submit()"'])
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $next, 'user_id' => $employee->id]) }}">روز بعد</a>
                @if($dateStr !== $today)
                    <a class="btn btn-secondary" href="{{ route('daily-logs.index', ['date' => $today, 'user_id' => $employee->id]) }}">امروز</a>
                @endif
            </div>
            @if($canManage)
                <div>
                    <label>کارمند / کارآموز</label>
                    <select name="user_id" onchange="this.form.submit()">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((int)$employee->id === (int)$emp->id)>
                                {{ $emp->name }} ({{ $roleLabel[$emp->role] ?? $emp->role }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="user_id" value="{{ $employee->id }}">
            @endif
        </form>
        <div class="daybook-stats">
            <div><span>تاریخ</span><strong>{{ jalali_date($date) }}</strong></div>
            <div><span>فرد</span><strong>{{ $employee->name }}</strong></div>
            <div><span>رویداد</span><strong>{{ $summary['count'] }}</strong></div>
            @if($settings['show_quantity'])
                <div><span>تعداد</span><strong>{{ $summary['quantity'] }}</strong></div>
            @endif
            <div><span>دقایق</span><strong>{{ $summary['minutes'] }}</strong></div>
            <div><span>با قبض</span><strong>{{ $summary['with_ticket'] }}</strong></div>
        </div>
    </section>

    @if($settings['editable'])
        <section class="panel daybook-compose" id="daybook-compose"
                 data-tickets-url="{{ $ticketSearchUrl }}">
            <h3>ثبت کار امروز</h3>
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('daily-logs.store') }}" class="daybook-form" id="daybook-form">
                @csrf
                <input type="hidden" name="work_date" value="{{ $dateStr }}">
                <input type="hidden" name="user_id" value="{{ $employee->id }}">
                <input type="hidden" name="reception_id" id="daybook-reception-id" value="{{ old('reception_id') }}">

                <div class="daybook-cats" id="daybook-cats">
                    @foreach($categories as $cat)
                        <label class="daybook-cat">
                            <input type="radio" name="daily_log_category_id" value="{{ $cat->id }}"
                                   data-ask-qty="{{ $cat->ask_quantity ? '1' : '0' }}"
                                   data-need-ticket="{{ $cat->needsReceipt() ? '1' : '0' }}"
                                   data-name="{{ $cat->name }}"
                                   @checked((string) old('daily_log_category_id') === (string) $cat->id)>
                            <span class="daybook-cat-mark">{{ $cat->mark }}</span>
                            <span>
                                <strong>{{ $cat->name }}</strong>
                                @if($cat->hint)<small>{{ $cat->hint }}</small>@endif
                                @if($cat->needsReceipt())<small class="daybook-ticket-badge">+ جستجوی قبض</small>@endif
                            </span>
                        </label>
                    @endforeach
                    <label class="daybook-cat">
                        <input type="radio" name="daily_log_category_id" value="" data-ask-qty="0" data-need-ticket="0" data-name=""
                               @checked(old('daily_log_category_id', '') === '')>
                        <span class="daybook-cat-mark">+</span>
                        <span><strong>آزاد</strong><small>بدون دسته</small></span>
                    </label>
                </div>

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

                <div class="accept-row accept-row-3">
                    <div>
                        <label>عنوان</label>
                        <input type="text" name="title" id="daybook-title" value="{{ old('title') }}" placeholder="خودکار از دسته + قبض پر می‌شود">
                    </div>
                    @if($settings['show_quantity'])
                        <div id="daybook-qty-wrap">
                            <label>تعداد</label>
                            <input type="number" name="quantity" min="1" max="9999" value="{{ old('quantity') }}" placeholder="مثلاً ۱">
                        </div>
                    @endif
                    <div>
                        <label>مدت (دقیقه)</label>
                        <input type="number" name="minutes" min="1" max="1440" value="{{ old('minutes') }}" placeholder="مثلاً ۳۰">
                    </div>
                </div>
                <div>
                    <label>شرح کار {{ $settings['require_note'] ? '' : '(اختیاری)' }}</label>
                    <textarea name="body" id="daybook-body" rows="3" placeholder="چه کاری روی این قبض / رویداد انجام شد؟" {{ $settings['require_note'] ? 'required' : '' }}>{{ old('body') }}</textarea>
                </div>
                <div class="actions" style="margin-top:8px;">
                    <button class="btn btn-primary" type="submit">ثبت در دفتر روز</button>
                </div>
            </form>
        </section>
    @else
        <div class="alert alert-error">ثبت برای این تاریخ بسته است (فقط {{ $settings['allow_past_days'] }} روز اخیر برای کارمند مجاز است).</div>
    @endif

    <section class="panel">
        <h3 style="margin-top:0;">رویدادهای این روز</h3>
        @forelse($entries as $entry)
            <article class="daybook-entry">
                <div class="daybook-entry-mark">{{ $entry->category?->mark ?: '•' }}</div>
                <div class="daybook-entry-main">
                    <strong>{{ $entry->displayTitle() }}</strong>
                    <div class="muted" style="font-size:11px;">
                        {{ $entry->category_name ?: 'آزاد' }}
                        @if($entry->ticketLabel())
                            · <a href="{{ route('receptions.show', $entry->reception_id) }}">{{ $entry->ticketLabel() }}</a>
                            @if($entry->reception?->customer) — {{ $entry->reception->customer->name }} @endif
                        @endif
                        @if($entry->quantity) · تعداد {{ $entry->quantity }} @endif
                        @if($entry->minutes) · {{ $entry->minutes }} دقیقه @endif
                        · {{ format_app_time($entry->created_at) }}
                    </div>
                    @if($entry->body)
                        <p style="margin:4px 0 0;font-size:12px;">{{ $entry->body }}</p>
                    @endif
                </div>
                @if($settings['editable'])
                    <form method="POST" action="{{ route('daily-logs.destroy', $entry) }}" data-confirm="این رویداد حذف شود؟">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">حذف</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="lead" style="margin:0;">هنوز رویدادی برای این روز ثبت نشده.</p>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var compose = document.getElementById('daybook-compose');
    if (!compose) return;
    var url = compose.getAttribute('data-tickets-url');
    var cats = document.getElementById('daybook-cats');
    var title = document.getElementById('daybook-title');
    var body = document.getElementById('daybook-body');
    var panel = document.getElementById('daybook-ticket-panel');
    var qtyWrap = document.getElementById('daybook-qty-wrap');
    var receptionId = document.getElementById('daybook-reception-id');
    var selectedBox = document.getElementById('daybook-ticket-selected');
    var listBox = document.getElementById('daybook-ticket-list');
    var qInput = document.getElementById('daybook-ticket-q');
    var searchBtn = document.getElementById('daybook-ticket-search-btn');
    var selectedTicket = null;
    var timer = null;

    function selectedCat() {
        return cats.querySelector('input[name="daily_log_category_id"]:checked');
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
        if (input && input.getAttribute('data-name') && title && !title.value && !selectedTicket) {
            title.placeholder = input.getAttribute('data-name');
        }
        refreshTitle();
    }

    function clearTicket() {
        selectedTicket = null;
        if (receptionId) receptionId.value = '';
        if (selectedBox) {
            selectedBox.classList.add('hidden');
            selectedBox.innerHTML = '';
        }
        if (listBox) listBox.innerHTML = '';
        refreshTitle();
    }

    function refreshTitle() {
        if (!title) return;
        var cat = selectedCat();
        var catName = cat ? (cat.getAttribute('data-name') || '') : '';
        if (selectedTicket) {
            title.value = (catName || 'کار روی قبض') + ' — ' + (selectedTicket.ticket_no || selectedTicket.receipt_no || '');
        }
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
        refreshTitle();
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
            body.value = body.value ? (body.value.replace(/\s+$/, '') + (body.value ? ' · ' : '') + h) : h;
            body.focus();
        });
    });

    document.getElementById('daybook-form')?.addEventListener('submit', function (e) {
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
