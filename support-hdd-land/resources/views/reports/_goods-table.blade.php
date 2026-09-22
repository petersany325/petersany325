<div class="table-wrap">
    <table class="compact-table">
        <thead>
        <tr>
            <th>تاریخ</th>
            <th>قبض</th>
            <th>مشتری</th>
            <th>کالا / سریال</th>
            <th>خدمات</th>
            <th>تعمیرکار</th>
            <th>وضعیت</th>
            <th>اجرت</th>
            <th>جمع</th>
            <th>پرداخت</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            @php
                $when = $dateField === 'delivered_at'
                    ? $r->delivered_at
                    : ($r->received_at ?: $r->created_at);
            @endphp
            <tr>
                <td dir="ltr">{{ jalali_like($when) }}</td>
                <td>{{ $r->ticket_no }}</td>
                <td>
                    {{ $r->customer?->name ?: '—' }}
                    <div class="muted" dir="ltr" style="font-size:10px;">{{ $r->customer?->phone }}</div>
                </td>
                <td>
                    {{ $r->product_name ?: '—' }}
                    @if($r->model)<div class="muted" style="font-size:10px;">{{ $r->model }}</div>@endif
                    @if($r->serial_number)<div class="muted" dir="ltr" style="font-size:10px;">{{ $r->serial_number }}</div>@endif
                </td>
                <td>
                    {{ $r->service_type ?: '—' }}
                    <div class="muted" style="font-size:10px;">{{ $r->repair_type ?: '' }}</div>
                </td>
                <td>{{ $r->technician?->name ?: '—' }}</td>
                <td><span class="badge badge-{{ $r->status }}">{{ $r->statusLabel() }}</span></td>
                <td>{{ toman((int) $r->labor_cost) }}</td>
                <td><strong>{{ toman((int) $r->total_amount) }}</strong></td>
                <td>{{ toman((int) $r->paid_amount) }}</td>
                <td><a class="btn btn-ghost btn-sm" href="{{ route('receptions.show', $r) }}">جزئیات</a></td>
            </tr>
        @empty
            <tr><td colspan="11">موردی یافت نشد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($rows, 'links'))
    <div style="margin-top:10px;">{{ $rows->links('partials.pagination') }}</div>
@endif
