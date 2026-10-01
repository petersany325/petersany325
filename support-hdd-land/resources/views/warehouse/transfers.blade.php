@extends('layouts.app')
@section('title', 'انتقال بین انبار | '.shop_name())
@section('page_title', 'جابجایی اجناس بین انبارها')
@section('content')
<div class="panel">
    <h2>انتقال بین انبار</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('warehouse-transfers.store') }}" class="accept-row accept-row-4" style="align-items:end;">
        @csrf
        <div>
            <label>از انبار</label>
            <select name="from_warehouse_id" required>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>به انبار</label>
            <select name="to_warehouse_id" required>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>کالا</label>
            <select name="part_id" required>
                @foreach($parts as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} — موجودی {{ $p->stock }} ({{ $p->warehouse?->name }})</option>
                @endforeach
            </select>
        </div>
        <div><label>تعداد</label><input type="number" name="quantity" min="1" value="1" required></div>
        <div style="grid-column:1/-1"><label>یادداشت</label><input name="note"></div>
        <div><button class="btn btn-primary" type="submit">ثبت انتقال</button></div>
    </form>

    <h3 style="margin-top:16px;">سوابق</h3>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>سند</th><th>از</th><th>به</th><th>کالا</th><th>تعداد</th><th>زمان</th></tr></thead>
            <tbody>
            @forelse($items as $t)
                <tr>
                    <td dir="ltr">{{ $t->doc_no }}</td>
                    <td>{{ $t->fromWarehouse?->name }}</td>
                    <td>{{ $t->toWarehouse?->name }}</td>
                    <td>{{ $t->part?->name }}</td>
                    <td>{{ $t->quantity }}</td>
                    <td>{{ jalali_like($t->created_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">هنوز انتقالی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links('partials.pagination') }}
</div>
@endsection
