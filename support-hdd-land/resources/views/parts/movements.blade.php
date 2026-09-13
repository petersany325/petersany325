@extends('layouts.app')
@section('title', 'گردش انبار | '.shop_name())
@section('page_title', 'کارتکس و گردش انبار')
@section('window_title', 'دفتر گردش موجودی قطعات')

@section('content')
@include('parts._nav', [
    'whTitle' => 'کارتکس / گردش انبار',
    'whSub' => 'رسید، حواله، مصرف قبض و تعدیل‌ها در یک دفتر',
])

<div class="wh-desk">
    <div class="wh-kpi-grid">
        <div class="wh-kpi tone-blue"><span>ورود (تعداد)</span><strong>{{ number_format($summary['in_qty']) }}</strong></div>
        <div class="wh-kpi tone-rose"><span>خروج (تعداد)</span><strong>{{ number_format($summary['out_qty']) }}</strong></div>
        <div class="wh-kpi tone-amber"><span>بهای ورود</span><strong>{{ number_format($summary['in_cost']) }}</strong></div>
        <div class="wh-kpi tone-teal"><span>بهای خروج / COGS</span><strong>{{ number_format($summary['out_cost']) }}</strong></div>
    </div>

    <form method="GET" class="panel wh-filters" style="margin-bottom:10px;">
        @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
        @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        <input type="text" name="q" value="{{ $q }}" placeholder="شماره سند / کالا / شرح">
        <select name="type">
            <option value="">همه انواع</option>
            <option value="in" @selected($type === 'in')>ورود</option>
            <option value="out" @selected($type === 'out')>خروج</option>
            <option value="adjust" @selected($type === 'adjust')>تعدیل</option>
        </select>
        <select name="doc_type">
            <option value="">همه اسناد</option>
            @foreach($docTypes as $key => $label)
                <option value="{{ $key }}" @selected($docType === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit">اعمال</button>
    </form>

    <div class="panel">
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>شماره سند</th>
                    <th>تاریخ</th>
                    <th>کالا</th>
                    <th>نوع سند</th>
                    <th>تعداد</th>
                    <th>بهای واحد</th>
                    <th>جمع بها</th>
                    <th>مانده</th>
                    <th>قبض / شرح</th>
                    <th>کاربر</th>
                </tr>
                </thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td dir="ltr">{{ $m->doc_no ?: '#'.$m->id }}</td>
                        <td>{{ jalali_like($m->created_at) }}</td>
                        <td>
                            @if($m->part_id)
                                <a href="{{ route('parts.show', $m->part_id) }}">{{ $m->part?->name }}</a>
                            @else — @endif
                        </td>
                        <td>{{ $m->docTypeLabel() }}</td>
                        <td class="{{ $m->quantity < 0 ? 'wh-neg' : 'wh-pos' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                        <td>{{ toman($m->unit_cost) }}</td>
                        <td>{{ toman($m->total_cost) }}</td>
                        <td>{{ number_format($m->stock_after) }}</td>
                        <td>
                            @if($m->reception_id)
                                <a href="{{ route('receptions.show', $m->reception_id) }}">{{ $m->reception?->ticket_no }}</a>
                            @endif
                            <div class="muted" style="font-size:11px;">{{ $m->note ?: '—' }}</div>
                        </td>
                        <td>{{ $m->user?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10">در این بازه گردشی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links('partials.pagination') }}
    </div>
</div>
@endsection
