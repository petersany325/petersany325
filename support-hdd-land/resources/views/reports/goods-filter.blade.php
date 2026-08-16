@extends('layouts.app')
@section('title', 'تفکیک کالا | '.shop_name())
@section('page_title', 'جستجوی پیشرفته کالا')
@section('window_title', 'فیلتر کامل قبض‌ها')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">گزارش تفکیک کالا — جستجوی پیشرفته</h2>
    <p class="muted" style="margin:0 0 10px;">مثلاً فقط ریکاوری، فقط تعمیر، وضعیت خاص، تعمیرکار، سریال و … در بازه {{ jalali_date($from) }} تا {{ jalali_date($to) }}.</p>
    @include('reports._period-quick')

    <form method="GET" action="{{ route('reports.goods-filter') }}" class="panel" style="margin:10px 0 0;padding:12px;background:#f8fafc;">
        <div class="accept-row accept-row-4" style="align-items:end;">
            <div>
                <label>جستجو کلی</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="قبض / مشتری / موبایل / سریال">
            </div>
            <div>
                <label>سریال</label>
                <input type="text" name="serial" value="{{ $filters['serial'] }}" class="field-latin" dir="ltr" placeholder="SERIAL">
            </div>
            <div>
                <label>کالا / مدل</label>
                <input type="text" name="product" value="{{ $filters['product'] }}" placeholder="مدل یا نام کالا">
            </div>
            <div>
                <label>فیلتر تاریخ روی</label>
                <select name="date_field">
                    <option value="received_at" @selected($filters['date_field'] === 'received_at')>تاریخ پذیرش</option>
                    <option value="delivered_at" @selected($filters['date_field'] === 'delivered_at')>تاریخ خروج</option>
                    <option value="created_at" @selected($filters['date_field'] === 'created_at')>تاریخ ثبت</option>
                </select>
            </div>
            <div>
                <label>وضعیت</label>
                <select name="status">
                    <option value="">همه</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع خدمات</label>
                <select name="service_type">
                    <option value="">همه</option>
                    @foreach($serviceTypes as $name)
                        <option value="{{ $name }}" @selected($filters['service_type'] === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع تعمیر</label>
                <select name="repair_type">
                    <option value="">همه</option>
                    @foreach($repairTypes as $name)
                        <option value="{{ $name }}" @selected($filters['repair_type'] === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع پذیرش</label>
                <select name="admission_type">
                    <option value="">همه</option>
                    @foreach($admissionTypes as $name)
                        <option value="{{ $name }}" @selected($filters['admission_type'] === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>تعمیرکار</label>
                <select name="technician_id">
                    <option value="">همه</option>
                    @foreach($technicians as $t)
                        <option value="{{ $t->id }}" @selected((int) $filters['technician_id'] === (int) $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>نوع ایراد</label>
                <select name="fault_type_id">
                    <option value="">همه</option>
                    @foreach($faultTypes as $f)
                        <option value="{{ $f->id }}" @selected((int) $filters['fault_type_id'] === (int) $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>محل دستگاه</label>
                <select name="custody">
                    <option value="">همه</option>
                    <option value="front_desk" @selected($filters['custody'] === 'front_desk')>نزد پذیرش</option>
                    <option value="with_technician" @selected($filters['custody'] === 'with_technician')>دست تعمیرکار</option>
                    <option value="returning" @selected($filters['custody'] === 'returning')>در حال بازگشت</option>
                </select>
            </div>
            <div>
                <label>وضعیت مالی</label>
                <select name="finance">
                    <option value="">همه</option>
                    <option value="unpaid" @selected($filters['finance'] === 'unpaid')>بدهکار</option>
                    <option value="paid" @selected($filters['finance'] === 'paid')>تسویه‌شده</option>
                    <option value="credit" @selected($filters['finance'] === 'credit')>نسیه خروج‌شده</option>
                </select>
            </div>
            <div>
                @include('partials.toggle', [
                    'name' => 'warranty_return',
                    'label' => 'فقط برگشت گارانتی',
                    'checked' => !empty($filters['warranty_return']),
                ])
            </div>
            <div class="actions" style="margin:0;">
                <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
                <a class="btn btn-ghost" href="{{ route('reports.goods-filter') }}">پاک</a>
            </div>
        </div>

        <div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['service_type' => 'بازیابی اطلاعات', 'date_field' => $filters['date_field']]) }}">فقط بازیابی اطلاعات</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['service_type' => 'تعمیر', 'date_field' => $filters['date_field']]) }}">فقط تعمیر</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['repair_type' => 'دیتا ریکاوری', 'date_field' => $filters['date_field']]) }}">دیتا ریکاوری</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['status' => 'ready', 'date_field' => $filters['date_field']]) }}">آماده تحویل</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['status' => 'unrepairable', 'date_field' => 'created_at']) }}">غیرقابل تعمیر</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('reports.goods-filter', ['finance' => 'unpaid', 'date_field' => $filters['date_field']]) }}">بدهکارها</a>
        </div>
    </form>
</div>

<div class="stats stats-compact" style="margin-bottom:12px;">
    <div class="stat"><div class="label">تعداد</div><div class="value">{{ number_format($totals['count']) }}</div></div>
    <div class="stat"><div class="label">اجرت</div><div class="value">{{ toman((int) $totals['labor']) }}</div></div>
    <div class="stat"><div class="label">قطعات</div><div class="value">{{ toman((int) $totals['parts']) }}</div></div>
    <div class="stat"><div class="label">جمع مبلغ</div><div class="value">{{ toman((int) $totals['total']) }}</div></div>
    <div class="stat"><div class="label">پرداخت</div><div class="value">{{ toman((int) $totals['paid']) }}</div></div>
    <div class="stat"><div class="label">مانده</div><div class="value">{{ toman((int) $totals['remaining']) }}</div></div>
</div>

@if($byService->isNotEmpty() || $byStatus->isNotEmpty())
<div class="split-2" style="margin-bottom:12px;">
    <div class="panel">
        <h3 style="margin-top:0;">تفکیک خدمات</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>خدمات</th><th>تعداد</th></tr></thead>
                <tbody>
                @forelse($byService as $name => $c)
                    <tr><td>{{ $name ?: '—' }}</td><td>{{ number_format((int) $c) }}</td></tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <h3 style="margin-top:0;">تفکیک وضعیت</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead>
                <tbody>
                @forelse($byStatus as $st => $c)
                    <tr><td>{{ $statuses[$st] ?? $st }}</td><td>{{ number_format((int) $c) }}</td></tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="panel">
    @include('reports._goods-table', ['rows' => $rows, 'dateField' => $filters['date_field']])
</div>
@endsection
