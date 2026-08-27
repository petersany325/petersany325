@extends('layouts.app')

@section('title', 'اعلان‌ها | '.shop_name())
@section('page_title', 'اعلان‌ها و پیام مشتری')
@section('window_title', 'صندوق پیام تعمیرگاه')

@section('content')
<div class="actions" style="margin-bottom:10px;">
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-ghost" type="submit">علامت‌گذاری همه به‌عنوان خوانده‌شده</button>
    </form>
</div>

<div class="panel" style="margin-bottom:12px;">
    <h3 style="margin:0 0 8px;">اعلان‌های من</h3>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>عنوان</th>
                <th>متن</th>
                <th>زمان</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($notifications as $n)
                <tr style="{{ $n->isUnread() ? 'background:rgba(20,184,166,.08);' : '' }}">
                    <td>
                        <strong>{{ $n->title }}</strong>
                        @if($n->isUnread())<span class="badge">جدید</span>@endif
                    </td>
                    <td>{{ $n->body }}</td>
                    <td>{{ jalali_like($n->created_at) }}</td>
                    <td>
                        <form method="POST" action="{{ route('notifications.read', $n) }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">باز کردن</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">اعلانی نیست.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $notifications->links('partials.pagination') }}
</div>

<div class="panel">
    <h3 style="margin:0 0 8px;">پیام‌های مشتریان (تیکت پشتیبانی)</h3>
    <p class="muted">مشتری از کارتابل می‌تواند درباره قبض سؤال یا درخواست بفرستد — استاندارد portal messaging.</p>
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>مشتری</th>
                <th>قبض</th>
                <th>اولویت</th>
                <th>پیام</th>
                <th>زمان</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($messages as $m)
                <tr style="{{ $m->isUnread() ? 'background:rgba(245,158,11,.1);' : '' }}">
                    <td>{{ $m->customer?->name }}</td>
                    <td>
                        @if($m->reception_id)
                            <a href="{{ route('receptions.show', $m->reception_id) }}">{{ $m->reception?->ticket_no }}</a>
                        @else
                            عمومی
                        @endif
                    </td>
                    <td>{{ $m->priorityLabel() }}</td>
                    <td>{{ $m->body }}</td>
                    <td>{{ jalali_like($m->created_at) }}</td>
                    <td><a class="btn btn-ghost" href="{{ route('notifications.messages.show', $m) }}">مشاهده</a></td>
                </tr>
            @empty
                <tr><td colspan="6">پیامی ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $messages->links('partials.pagination') }}
</div>
@endsection
