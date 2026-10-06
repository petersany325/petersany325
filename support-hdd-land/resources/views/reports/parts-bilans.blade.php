@extends('layouts.app')
@section('title', 'بیلان قطعه تعمیر | '.shop_name())
@section('page_title', 'گزارش بیلان قطعه تعمیر')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">بیلان قطعه تعمیر</h2>
    <p class="lead" style="margin:0 0 10px;">فروش قطعه روی قبض − بهای خرید مصرف‌شده = سود بیلان (بر اساس قیمت خرید کارت کالا).</p>
    <form method="GET" action="{{ route('reports.parts-bilans') }}" class="actions" style="gap:8px;flex-wrap:wrap;align-items:end;">
        <div>
            <label>تعمیرکار</label>
            <select name="technician_id">
                <option value="0">همه</option>
                @foreach($technicians as $t)
                    <option value="{{ $t->id }}" @selected($techId === $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
    </form>
</div>

<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">تعداد مصرف</div><div class="value">{{ number_format($totals['qty']) }}</div></div>
    <div class="stat"><div class="label">فروش</div><div class="value">{{ number_format($totals['sale']) }}</div></div>
    <div class="stat"><div class="label">بهای خرید</div><div class="value">{{ number_format($totals['purchase']) }}</div></div>
    <div class="stat"><div class="label">سود بیلان</div><div class="value">{{ number_format($totals['profit']) }}</div></div>
</div>

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin-top:0;">خلاصه به تفکیک قطعه</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قطعه</th>
                <th>تعداد</th>
                <th>فروش</th>
                <th>بهای خرید</th>
                <th>سود</th>
                <th>حاشیه٪</th>
            </tr>
            </thead>
            <tbody>
            @forelse($byPart as $row)
                <tr>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ number_format($row->qty) }}</td>
                    <td>{{ toman($row->sale_amount) }}</td>
                    <td>{{ toman($row->purchase_cost) }}</td>
                    <td>{{ toman($row->profit) }}</td>
                    <td>{{ $row->margin !== null ? $row->margin.'٪' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">در این بازه مصرفی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3 style="margin-top:0;">جزئیات قطعه × تعمیرکار</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قطعه</th>
                <th>تعمیرکار</th>
                <th>تعداد</th>
                <th>فروش</th>
                <th>بهای خرید</th>
                <th>سود</th>
                <th>حاشیه٪</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ $row->technician_name ?: '—' }}</td>
                    <td>{{ number_format((int) $row->qty) }}</td>
                    <td>{{ toman((int) $row->sale_amount) }}</td>
                    <td>{{ toman((int) $row->purchase_cost) }}</td>
                    <td>{{ toman((int) $row->profit) }}</td>
                    <td>{{ $row->margin !== null ? $row->margin.'٪' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">ردیفی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
