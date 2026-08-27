@extends('layouts.app')
@section('title', 'کارتکس '.$\2.' | '.shop_name())
@section('page_title', 'کارتکس کالا')
@section('window_title', 'کارت حساب انبار — '.$part->name)

@section('content')
@include('parts._nav', [
    'whTitle' => 'کارتکس: '.$part->name,
    'whSub' => 'موجودی، بهای تمام‌شده و گردش این کالا',
    'whActions' => '<a class="btn btn-ghost" href="'.route('parts.edit', $part).'">ویرایش کارت</a>',
])

<div class="wh-desk">
    <div class="wh-kpi-grid">
        <div class="wh-kpi tone-teal"><span>موجودی</span><strong>{{ number_format($part->stock) }}</strong><small>حداقل {{ $part->min_stock }}</small></div>
        <div class="wh-kpi tone-amber"><span>بهای خرید</span><strong>{{ number_format($part->purchase_price) }}</strong><small>فی واحد</small></div>
        <div class="wh-kpi tone-blue"><span>ارزش انبار</span><strong>{{ number_format($valueCost) }}</strong><small>تعداد × بهای خرید</small></div>
        <div class="wh-kpi tone-rose"><span>ارزش فروش</span><strong>{{ number_format($valueSale) }}</strong><small>فی فروش {{ number_format($part->sale_price) }}</small></div>
    </div>

    <div class="split-2">
        <div class="panel">
            <h3 style="margin-top:0;">شناسنامه کالا</h3>
            <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                <div><span class="muted">انبار</span><div>{{ $part->warehouse?->name ?: '—' }}</div></div>
                <div><span class="muted">کد</span><div dir="ltr">{{ $part->code ?: '—' }}</div></div>
                <div><span class="muted">وضعیت</span><div>{{ $part->is_active ? 'فعال' : 'غیرفعال' }}</div></div>
                <div><span class="muted">برند</span><div>{{ $part->brand ?: '—' }}</div></div>
                <div><span class="muted">مدل</span><div>{{ $part->model ?: '—' }}</div></div>
            </div>
            <form method="POST" action="{{ route('parts.stock', $part) }}" class="accept-row accept-row-3" style="margin-top:12px;align-items:end;">
                @csrf
                <div>
                    <label>تعدیل موجودی</label>
                    <input type="number" name="quantity" value="1" required title="مثبت=ورود، منفی=خروج">
                </div>
                <div>
                    <label>بهای واحد (تومان)</label>
                    <input type="number" name="unit_cost" min="0" value="{{ $part->purchase_price }}">
                </div>
                <div class="full">
                    <label>شرح سند</label>
                    <input type="text" name="note" placeholder="دلیل تعدیل">
                </div>
                <div class="actions" style="margin:0;">
                    <button class="btn btn-secondary" type="submit">ثبت تعدیل + سند حسابداری</button>
                </div>
            </form>
        </div>
        <div class="panel">
            <h3 style="margin-top:0;">گردش کارتکس</h3>
            <div class="table-wrap">
                <table class="compact-table">
                    <thead>
                    <tr><th>سند</th><th>نوع</th><th>تعداد</th><th>بهای واحد</th><th>مانده</th><th>قبض</th><th>زمان</th></tr>
                    </thead>
                    <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td dir="ltr">{{ $m->doc_no ?: '#'.$m->id }}</td>
                            <td>{{ $m->docTypeLabel() }}</td>
                            <td class="{{ $m->quantity < 0 ? 'wh-neg' : 'wh-pos' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                            <td>{{ toman($m->unit_cost) }}</td>
                            <td>{{ number_format($m->stock_after) }}</td>
                            <td>
                                @if($m->reception_id)
                                    <a href="{{ route('receptions.show', $m->reception_id) }}">{{ $m->reception?->ticket_no }}</a>
                                @else — @endif
                            </td>
                            <td>
                                {{ jalali_like($m->created_at) }}
                                <div class="muted" style="font-size:10px;">{{ $m->user?->name }}@if($m->note) — {{ $m->note }}@endif</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">گردشی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links('partials.pagination') }}
        </div>
    </div>
</div>
@endsection
