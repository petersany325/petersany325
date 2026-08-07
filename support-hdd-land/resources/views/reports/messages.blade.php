@extends('layouts.app')
@section('title', 'گزارش پیام مشتری | سرزمین هارد')
@section('page_title', 'گزارش پیام‌های کارتابل مشتری')
@section('window_title', 'تیکت پشتیبانی مشتریان')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">پیام‌های مشتری</h2>
    <p class="muted" style="margin-top:0;">پیگیری درخواست‌هایی که مشتری از کارتابل درباره قبض ارسال کرده است.</p>
    @include('reports._filters')
    <div class="stats stats-compact">
        <div class="stat"><div class="label">کل</div><div class="value">{{ number_format($summary['total']) }}</div></div>
        <div class="stat"><div class="label">خوانده‌نشده</div><div class="value">{{ number_format($summary['unread']) }}</div></div>
        <div class="stat"><div class="label">فوری</div><div class="value">{{ number_format($summary['urgent']) }}</div></div>
        <div class="stat"><div class="label">میانگین پاسخ (ساعت)</div><div class="value">{{ $avgResponseHours !== null ? number_format((float) $avgResponseHours, 1) : '—' }}</div></div>
    </div>
</div>

<div class="panel">
    <h3 style="margin-top:0;">آخرین پیام‌ها</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>زمان</th>
                <th>مشتری</th>
                <th>قبض</th>
                <th>اولویت</th>
                <th>وضعیت</th>
                <th>متن</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $m)
                <tr style="{{ $m->isUnread() ? 'background:rgba(245,158,11,.08);' : '' }}">
                    <td>{{ $m->created_at?->format('Y/m/d H:i') }}</td>
                    <td>{{ $m->customer?->name }}</td>
                    <td>
                        @if($m->reception_id)
                            <a href="{{ route('receptions.show', $m->reception_id) }}">{{ $m->reception?->ticket_no }}</a>
                        @else عمومی @endif
                    </td>
                    <td>{{ $m->priorityLabel() }}</td>
                    <td>{{ $m->isUnread() ? 'جدید' : 'دیده شده' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($m->body, 80) }}</td>
                    <td><a class="btn btn-ghost" href="{{ route('notifications.messages.show', $m) }}">باز</a></td>
                </tr>
            @empty
                <tr><td colspan="7">پیامی در این بازه نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
