@extends('layouts.app')
@section('title', 'انبار قطعات | '.shop_name())
@section('page_title', 'انبار قطعات')
@section('window_title', 'میز انبار — موجودی و ارزش')

@section('content')
@include('parts._nav', [
    'whTitle' => 'میز انبار',
    'whSub' => 'موجودی، ارزش ریالی و آخرین گردش قطعات تعمیرگاه',
])

<div class="wh-desk">
    <div class="wh-kpi-grid">
        <div class="wh-kpi tone-teal">
            <span>کالاهای فعال</span>
            <strong>{{ number_format($stats['sku']) }}</strong>
            <small>SKU در انبار</small>
        </div>
        <div class="wh-kpi tone-blue">
            <span>جمع تعداد</span>
            <strong>{{ number_format($stats['qty']) }}</strong>
            <small>موجودی عددی</small>
        </div>
        <div class="wh-kpi tone-amber">
            <span>ارزش به‌بهای خرید</span>
            <strong>{{ number_format($stats['value_cost']) }}</strong>
            <small>حساب موجودی ۱۳۱۰</small>
        </div>
        <div class="wh-kpi tone-rose">
            <span>کم‌موجود</span>
            <strong>{{ number_format($stats['low']) }}</strong>
            <small>زیر حداقل</small>
        </div>
    </div>

    <div class="wh-toolbar panel">
        <form method="GET" class="wh-filters">
            <input type="text" name="q" value="{{ $q }}" placeholder="نام، کد، برند، مدل" data-ascii-en>
            <select name="warehouse_id">
                <option value="">همه انبارها</option>
                @foreach(($warehouses ?? []) as $wh)
                    <option value="{{ $wh->id }}" @selected((int)($warehouseId ?? 0) === (int)$wh->id)>{{ $wh->name }}</option>
                @endforeach
            </select>
            <select name="filter">
                <option value="all" @selected($filter === 'all')>همه</option>
                <option value="low" @selected($filter === 'low')>فقط کم‌موجود</option>
                <option value="active" @selected($filter === 'active')>فعال</option>
                <option value="inactive" @selected($filter === 'inactive')>غیرفعال</option>
            </select>
            <button class="btn btn-secondary" type="submit">جستجو</button>
            <a class="btn btn-ghost" href="{{ route('parts.index') }}">پاک</a>
        </form>
        <div class="wh-toolbar-actions">
            <a class="btn btn-primary" href="{{ route('parts.receipt') }}">رسید ورود</a>
            <a class="btn btn-secondary" href="{{ route('parts.create') }}">کالای جدید</a>
        </div>
    </div>

    <div class="wh-panels">
        <section class="panel">
            <header class="wh-panel-head">
                <h3>کارت کالاها</h3>
                <span class="muted">ارزش فروش تقریبی: {{ number_format($stats['value_sale']) }} تومان</span>
            </header>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead>
                    <tr>
                        <th>کد</th>
                        <th>نام</th>
                        <th>انبار</th>
                        <th>برند/مدل</th>
                        <th>موجودی</th>
                        <th>بهای خرید</th>
                        <th>فی فروش</th>
                        <th>ارزش انبار</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($parts as $part)
                        <tr class="{{ $part->isLowStock() ? 'wh-row-low' : '' }}">
                            <td dir="ltr">{{ $part->code ?: '—' }}</td>
                            <td>
                                <a href="{{ route('parts.show', $part) }}">{{ $part->name }}</a>
                                @if($part->isLowStock())<span class="badge badge-cancelled">کم</span>@endif
                                @unless($part->is_active)<span class="badge">غیرفعال</span>@endunless
                            </td>
                            <td>{{ $part->warehouse?->name ?: '—' }}</td>
                            <td>{{ trim($part->brand.' '.$part->model) ?: '—' }}</td>
                            <td><strong>{{ number_format($part->stock) }}</strong></td>
                            <td>{{ toman($part->purchase_price) }}</td>
                            <td>{{ toman($part->sale_price) }}</td>
                            <td>{{ toman((int)$part->stock * (int)$part->purchase_price) }}</td>
                            <td class="wh-row-actions">
                                <a class="btn btn-ghost" href="{{ route('parts.show', $part) }}">کارتکس</a>
                                <a class="btn btn-ghost" href="{{ route('parts.edit', $part) }}">ویرایش</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">قطعه‌ای ثبت نشده. از «کالای جدید» شروع کنید.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $parts->links('partials.pagination') }}
        </section>

        <section class="panel">
            <header class="wh-panel-head">
                <h3>آخرین گردش انبار</h3>
                <a href="{{ route('parts.movements') }}">همه</a>
            </header>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead><tr><th>سند</th><th>کالا</th><th>نوع</th><th>تعداد</th><th>زمان</th></tr></thead>
                    <tbody>
                    @forelse($recent as $m)
                        <tr>
                            <td dir="ltr">{{ $m->doc_no ?: '#'.$m->id }}</td>
                            <td>{{ $m->part?->name ?: '—' }}</td>
                            <td>{{ $m->docTypeLabel() }}</td>
                            <td class="{{ $m->quantity < 0 ? 'wh-neg' : 'wh-pos' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                            <td>{{ jalali_like($m->created_at) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">گردشی ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
