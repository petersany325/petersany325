@extends('layouts.app')

@section('title', 'کارتابل ارجاع | سرزمین هارد')
@section('page_title', 'ارجاع دستگاه / کارتابل تعمیر')
@section('window_title', 'تأیید دریافت و هاردهای نزد تعمیرکار')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin:0 0 8px;">در انتظار تأیید دریافت</h3>
    <p class="muted" style="margin:0 0 12px;">طبق استاندارد Chain of Custody: هر انتقال دستگاه باید با تأیید گیرنده ثبت شود.</p>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>سریال</th>
                <th>نوع</th>
                <th>از</th>
                <th>یادداشت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pending as $item)
                <tr>
                    <td>
                        <a href="{{ route('receptions.show', $item->reception_id) }}">{{ $item->reception?->ticket_no }}</a>
                        <div class="muted">{{ $item->reception?->customer?->name }}</div>
                    </td>
                    <td dir="ltr">{{ $item->serial_snapshot ?: '—' }}</td>
                    <td>{{ $item->directionLabel() }}</td>
                    <td>{{ $item->fromUser?->name }}</td>
                    <td>{{ $item->note ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('handoffs.respond', $item) }}" class="actions" style="flex-wrap:wrap;">
                            @csrf
                            <input type="text" name="response_note" placeholder="یادداشت (اختیاری)" style="min-width:140px;">
                            <button class="btn btn-primary" name="decision" value="accepted" type="submit">بله، دریافت کردم</button>
                            <button class="btn btn-ghost" name="decision" value="rejected" type="submit">خیر، دریافت نکردم</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">ارجاع در انتظاری نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->technician)
<div class="panel">
    <h3 style="margin:0 0 8px;">هارد دیسک‌های دست تعمیر (نزد من)</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>قبض</th>
                <th>مشتری</th>
                <th>کالا / سریال</th>
                <th>وضعیت</th>
                <th>بازگشت به پذیرش</th>
            </tr>
            </thead>
            <tbody>
            @forelse($inHand as $row)
                <tr>
                    <td><a href="{{ route('receptions.show', $row) }}">{{ $row->ticket_no }}</a></td>
                    <td>{{ $row->customer?->name }}</td>
                    <td>{{ $row->product_name }} <span class="muted" dir="ltr">{{ $row->serial_number }}</span></td>
                    <td><span class="badge badge-{{ $row->status }}">{{ $row->statusLabel() }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('receptions.handoffs.store', $row) }}" class="actions">
                            @csrf
                            <input type="hidden" name="direction" value="to_front_desk">
                            <input type="text" name="note" placeholder="آماده تحویل به منشی؟" style="min-width:160px;">
                            <button class="btn btn-primary" type="submit">ارجاع به پذیرش</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">فعلاً دستگاهی نزد شما نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
