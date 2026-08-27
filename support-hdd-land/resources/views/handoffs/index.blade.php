@extends('layouts.app')

@section('title', 'کارتابل ارجاع | سرزمین هارد')
@section('page_title', 'ارجاع دستگاه / کارتابل تعمیر')
@section('window_title', 'جستجو، تأیید دریافت و گزارش محل دستگاه')

@section('content')
<div class="handoff-desk">
    <section class="panel handoff-toolbar">
        <form method="GET" action="{{ route('handoffs.index') }}" class="handoff-search accept-row accept-row-4" style="align-items:end;">
            <div>
                <label>شماره قبض</label>
                <input type="text" name="ticket_no" value="{{ $ticket }}" placeholder="مثلاً R-1404..." dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>سریال</label>
                <input type="text" name="serial" value="{{ $serial }}" placeholder="Serial…" dir="ltr" style="text-align:left;">
            </div>
            <div>
                <label>جستجوی کلی</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="قبض / سریال / مشتری / موبایل">
            </div>
            <div>
                <label>نمایش</label>
                <select name="status">
                    <option value="pending" @selected($status === 'pending')>در انتظار تأیید</option>
                    <option value="in_hand" @selected($status === 'in_hand')>دست تعمیرکار</option>
                    <option value="accepted" @selected($status === 'accepted')>تأیید شده</option>
                    <option value="rejected" @selected($status === 'rejected')>رد شده</option>
                    <option value="all" @selected($status === 'all')>همه ارجاع‌ها</option>
                </select>
            </div>
            <div class="actions" style="grid-column:1/-1;margin:0;flex-wrap:wrap;">
                <button class="btn btn-primary" type="submit">جستجو</button>
                <a class="btn btn-ghost" href="{{ route('handoffs.index') }}">پاک کردن</a>
                @if(auth()->user()->canAccess('reports.custody'))
                    <a class="btn btn-secondary" href="{{ route('reports.custody', array_filter(['ticket_no' => $ticket, 'serial' => $serial, 'q' => $q])) }}">گزارش کامل ارجاع</a>
                @endif
            </div>
        </form>
        <div class="stats stats-compact" style="margin-top:10px;">
            <div class="stat"><div class="label">در انتظار</div><div class="value">{{ number_format($stats['pending']) }}</div></div>
            <div class="stat"><div class="label">تأیید امروز</div><div class="value">{{ number_format($stats['accepted_today']) }}</div></div>
            <div class="stat"><div class="label">رد امروز</div><div class="value">{{ number_format($stats['rejected_today']) }}</div></div>
            <div class="stat"><div class="label">دست تعمیر</div><div class="value">{{ number_format($stats['in_hand']) }}</div></div>
        </div>
    </section>

    @if($status !== 'in_hand')
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
                    <tr><td colspan="6">ارجاع در انتظاری نیست{{ ($ticket || $serial || $q) ? ' (با این فیلتر)' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($status === 'in_hand' || $status === 'pending' || auth()->user()->technician || auth()->user()->canAccess('receptions'))
    <div class="panel" style="margin-bottom:12px;">
        <h3 style="margin:0 0 8px;">دست تعمیرکار — گزارش کار و بازگشت</h3>
        <p class="muted" style="margin:0 0 10px;">روی هر قبض باید گزارش کار ثبت شود؛ بعد از آن ارجاع بازگشت به منشی/حسابدار فعال می‌شود. بدون این‌ها اعلام هزینه و خروج قفل است.</p>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>قبض</th>
                    <th>مشتری / سریال</th>
                    <th>تعمیرکار</th>
                    <th>گزارش کار</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($inHand as $row)
                    @php
                        $owns = auth()->user()->technician && (int) $row->custody_technician_id === (int) auth()->user()->technician->id;
                        $hasReport = (bool) $row->latestWorkReport;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('receptions.show', $row) }}">{{ $row->ticket_no }}</a>
                            <div><span class="badge badge-{{ $row->status }}">{{ $row->statusLabel() }}</span></div>
                        </td>
                        <td>
                            {{ $row->customer?->name }}
                            <div class="muted">{{ $row->product_name }} <span dir="ltr">{{ $row->serial_number }}</span></div>
                        </td>
                        <td>{{ $row->custodyTechnician?->name ?: $row->technician?->name ?: '—' }}</td>
                        <td>
                            @if($hasReport)
                                <span class="pill pill-ok">ثبت شده</span>
                                <div class="muted">{{ $row->latestWorkReport->summary }}</div>
                            @else
                                <span class="pill pill-off">ثبت نشده</span>
                            @endif
                        </td>
                        <td>
                            @if($owns)
                                <form method="POST" action="{{ route('receptions.work-report', $row) }}" style="margin-bottom:8px;">
                                    @csrf
                                    <input type="text" name="summary" required maxlength="500" placeholder="گزارش کار این قبض…" style="min-width:200px;width:100%;margin-bottom:4px;">
                                    <div class="actions" style="margin:0;">
                                        <button class="btn btn-primary" type="submit">ثبت گزارش کار</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('receptions.handoffs.store', $row) }}" class="actions">
                                    @csrf
                                    <input type="hidden" name="direction" value="to_front_desk">
                                    <input type="text" name="note" placeholder="یادداشت بازگشت" style="min-width:140px;" @disabled(! $hasReport)>
                                    <button class="btn btn-secondary" type="submit" @disabled(! $hasReport)>ارجاع به پذیرش</button>
                                </form>
                                @unless($hasReport)
                                    <div class="muted" style="margin-top:4px;">اول گزارش کار، بعد ارجاع بازگشت</div>
                                @endunless
                            @else
                                <a class="btn btn-ghost" href="{{ route('receptions.show', $row) }}">جزئیات قبض</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">دستگاهی نزد تعمیرکار نیست{{ ($ticket || $serial || $q) ? ' (با این فیلتر)' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($history->isNotEmpty())
    <div class="panel">
        <h3 style="margin:0 0 8px;">نتایج / تاریخچه ارجاع</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>قبض</th>
                    <th>سریال</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>از</th>
                    <th>به</th>
                    <th>زمان</th>
                </tr>
                </thead>
                <tbody>
                @foreach($history as $h)
                    <tr>
                        <td>
                            @if($h->reception_id)
                                <a href="{{ route('receptions.show', $h->reception_id) }}">{{ $h->reception?->ticket_no }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td dir="ltr">{{ $h->serial_snapshot ?: '—' }}</td>
                        <td>{{ $h->directionLabel() }}</td>
                        <td>
                            @if($h->status === 'accepted')<span class="pill pill-ok">تأیید</span>
                            @elseif($h->status === 'rejected')<span class="pill pill-off">رد</span>
                            @else<span class="pill">انتظار</span>@endif
                        </td>
                        <td>{{ $h->fromUser?->name ?: '—' }}</td>
                        <td>{{ $h->toTechnician?->name ?: 'پذیرش' }}</td>
                        <td>{{ jalali_like($h->responded_at ?: $h->created_at) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(auth()->user()->canAccess('reports.custody'))
    <div class="panel" style="margin-top:12px;">
        <h3 style="margin:0 0 6px;">گزارش‌های این بخش</h3>
        <p class="muted" style="margin:0 0 8px;">گزارش Chain of Custody، محل فعلی دستگاه و عملکرد ارجاع به تفکیک تعمیرکار.</p>
        <div class="actions" style="flex-wrap:wrap;">
            <a class="btn btn-primary" href="{{ route('reports.custody') }}">گزارش ارجاع / محل دستگاه</a>
            <a class="btn btn-secondary" href="{{ route('reports.custody', ['ticket_no' => $ticket, 'serial' => $serial, 'q' => $q]) }}">همین فیلتر در گزارش</a>
            @if(auth()->user()->canAccess('reports.technicians'))
                <a class="btn btn-ghost" href="{{ route('reports.technicians') }}">گزارش تعمیرکاران</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
