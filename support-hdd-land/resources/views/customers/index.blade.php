@extends('layouts.app')
@section('title', 'مشتریان | '.shop_name())
@section('page_title', 'مدیریت مشتریان')
@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div>
            <h2>{{ ($filter ?? '') === 'blacklist' ? 'لیست سیاه مشتریان' : 'مشتریان' }}</h2>
            <p class="lead">ویرایش، حذف، لیست سیاه، و جلوگیری از موبایل/نام تکراری</p>
        </div>
        <div class="actions" style="margin:0;">
            <a class="btn btn-ghost" href="{{ route('customers.index', ['filter' => 'blacklist']) }}">لیست سیاه</a>
            <a class="btn btn-ghost" href="{{ route('device-blacklists.index') }}">لیست سیاه دستگاه</a>
            <a class="btn btn-primary" href="{{ route('customers.create') }}">مشتری جدید</a>
        </div>
    </div>

    <form class="ticket-search-bar" method="GET" style="margin:8px 0;" id="customer-search-form"
          data-suggest-url="{{ route('customers.suggest') }}"
          data-filter="{{ $filter ?? '' }}">
        @if(!empty($filter))
            <input type="hidden" name="filter" value="{{ $filter }}">
        @endif
        <div class="field" style="position:relative;flex:1;">
            <label>جستجو</label>
            <input type="text" name="q" id="customer-search-q" value="{{ $q }}" placeholder="نام را بنویسید — پیشنهاد زنده از بانک…" data-barcode autocomplete="off">
            <div class="customer-pick-list" id="customer-search-pick" hidden></div>
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-secondary" type="submit">جستجو در جدول</button>
            @if($q !== '' || ($filter ?? '') !== '')
                <a class="btn btn-ghost" href="{{ route('customers.index', array_filter(['filter' => $filter ?? null])) }}">پاک</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>تلفن</th>
                    <th>کد ملی</th>
                    <th>شغل</th>
                    <th>نحوه آشنایی</th>
                    <th>وضعیت</th>
                    <th>قبض</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>
                        {{ $customer->displayName() }}
                        @if($customer->gender)
                            <span class="muted" style="font-size:11px;">({{ $customer->genderLabel() }})</span>
                        @endif
                    </td>
                    <td dir="ltr">{{ $customer->phone }}</td>
                    <td>{{ $customer->national_code ?: '—' }}</td>
                    <td>{{ $customer->job ?: '—' }}</td>
                    <td>{{ $customer->referralSource?->name ?: '—' }}</td>
                    <td>
                        @if($customer->is_blacklisted)
                            <span class="pill pill-off">لیست سیاه</span>
                        @else
                            <span class="pill pill-ok">عادی</span>
                        @endif
                    </td>
                    <td>{{ $customer->receptions_count ?? $customer->receptions()->count() }}</td>
                    <td>
                        <div class="actions" style="margin:0;">
                            <a class="btn btn-ghost" href="{{ route('customers.show', $customer) }}">نمایش</a>
                            <a class="btn btn-secondary" href="{{ route('customers.edit', $customer) }}">ویرایش</a>
                            @php
                                $rc = (int) ($customer->receptions_count ?? 0);
                                $confirm = $rc > 0
                                    ? "مشتری «{$customer->name}» {$rc} قبض دارد. از فهرست مشتریان حذف می‌شود ولی قبض‌ها می‌مانند. ادامه؟"
                                    : "مشتری «{$customer->name}» حذف شود؟";
                            @endphp
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline;" data-confirm="{{ $confirm }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">مشتری‌ای یافت نشد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links('partials.pagination') }}
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('customer-search-form');
    if (!form) return;
    var url = form.getAttribute('data-suggest-url') || '';
    var filter = form.getAttribute('data-filter') || '';
    var input = document.getElementById('customer-search-q');
    var pick = document.getElementById('customer-search-pick');
    var timer = null;
    var seq = 0;
    function clearPick() { if (!pick) return; pick.innerHTML = ''; pick.hidden = true; }
    function render(list) {
        if (!pick) return;
        pick.innerHTML = '';
        if (!list.length) {
            pick.hidden = false;
            var e = document.createElement('div');
            e.className = 'customer-pick-empty';
            e.textContent = 'نتیجه‌ای نیست — Enter برای جستجوی جدول.';
            pick.appendChild(e);
            return;
        }
        list.forEach(function (c) {
            var a = document.createElement('a');
            a.href = '{{ url('/customers') }}/' + c.id;
            a.className = 'customer-pick-item';
            a.style.textDecoration = 'none';
            var name = document.createElement('span');
            name.className = 'customer-pick-name';
            name.textContent = c.display_name || c.name || '—';
            var meta = document.createElement('span');
            meta.className = 'customer-pick-meta';
            meta.textContent = [c.phone, c.visits != null ? (c.visits + ' قبض') : '', c.is_blacklisted ? 'لیست سیاه' : ''].filter(Boolean).join(' · ');
            a.appendChild(name);
            a.appendChild(meta);
            pick.appendChild(a);
        });
        pick.hidden = false;
    }
    function run() {
        var q = (input.value || '').trim();
        if (q.length < 1) { clearPick(); return; }
        var s = ++seq;
        var u = url + '?q=' + encodeURIComponent(q) + (filter ? '&filter=' + encodeURIComponent(filter) : '');
        fetch(u, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (s !== seq) return;
                render((data && data.customers) || []);
            })
            .catch(function () {});
    }
    if (input) {
        input.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(run, 220);
        });
    }
    document.addEventListener('click', function (e) {
        if (pick && !pick.contains(e.target) && e.target !== input) clearPick();
    });
})();
</script>
@endpush
