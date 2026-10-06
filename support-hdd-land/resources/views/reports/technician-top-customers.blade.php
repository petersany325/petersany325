@extends('layouts.app')
@section('title', 'پرمراجعه‌ترین مشتری تعمیرکار | '.shop_name())
@section('page_title', 'بیشترین مراجعه‌کننده تعمیرکار')

@section('content')
@include('reports._settings')

<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">بیشترین مراجعه‌کننده تعمیرکار</h2>
    <p class="lead" style="margin:0 0 10px;">رتبه‌بندی مشتریان هر تعمیرکار بر اساس تعداد قبض در بازه.</p>
    <form method="GET" action="{{ route('reports.technician-top-customers') }}" class="actions" style="gap:8px;flex-wrap:wrap;align-items:end;">
        <div>
            <label>تعمیرکار</label>
            <select name="technician_id">
                <option value="0">همه تعمیرکاران</option>
                @foreach($technicians as $t)
                    <option value="{{ $t->id }}" @selected($techId === $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-secondary" type="submit">اعمال فیلتر</button>
    </form>
</div>

@forelse($topPerTech as $group)
    @php $tech = $group->first()?->technician; @endphp
    <div class="panel" style="margin-bottom:12px;">
        <h3 style="margin-top:0;">
            @if($tech)
                <a href="{{ route('reports.technicians.show', $tech) }}">{{ $tech->name }}</a>
            @else
                تعمیرکار نامشخص
            @endif
        </h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>مشتری</th>
                    <th>موبایل</th>
                    <th>تعداد مراجعه</th>
                    <th>مبلغ قبض</th>
                    <th>پرداخت‌شده</th>
                    <th>آخرین مراجعه</th>
                </tr>
                </thead>
                <tbody>
                @foreach($group as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            @if($row->customer)
                                <a href="{{ route('reports.customers.show', $row->customer) }}">{{ $row->customer->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td dir="ltr">{{ $row->customer?->phone ?: '—' }}</td>
                        <td>{{ number_format((int) $row->visits) }}</td>
                        <td>{{ toman((int) $row->billed) }}</td>
                        <td>{{ toman((int) $row->paid) }}</td>
                        <td>{{ $row->last_visit ? jalali_like($row->last_visit) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="panel"><p class="muted" style="margin:0;">در این بازه مراجعی ثبت نشده.</p></div>
@endforelse
@endsection
