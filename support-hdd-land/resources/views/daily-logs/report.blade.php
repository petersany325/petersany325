@extends('layouts.app')
@section('title', 'گزارش دفتر روز | سرزمین هارد')
@section('page_title', 'گزارش دفتر روز')
@section('window_title', 'گزارش رویدادهای کارمندان')

@section('content')
<div class="daybook">
    <section class="daybook-hero panel">
        <div>
            <h2>گزارش دفتر روز</h2>
            <p class="lead" style="margin:4px 0 0;">مرور رویدادهای ثبت‌شده همه کارمندان بر اساس بازه تاریخ</p>
        </div>
        <div class="daybook-hero-actions">
            <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">ثبت امروز</a>
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات (ادمین)</a>
            @endif
        </div>
    </section>

    <form method="GET" class="panel daybook-toolbar" style="display:flex;flex-wrap:wrap;gap:8px;align-items:end;">
        <div>
            <label>از تاریخ</label>
            @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
        </div>
        <div>
            <label>تا تاریخ</label>
            @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        </div>
        <div>
            <label>کارمند</label>
            <select name="user_id">
                <option value="">همه</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected((int)$employeeId === (int)$emp->id)>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
    </form>

    <section class="panel">
        <h3 style="margin-top:0;">خلاصه به تفکیک کارمند</h3>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead><tr><th>کارمند</th><th>تعداد رویداد</th><th>جمع تعداد</th><th></th></tr></thead>
                <tbody>
                @forelse($byEmployee as $row)
                    <tr>
                        <td>{{ $row->user?->name ?: '—' }}</td>
                        <td>{{ $row->cnt }}</td>
                        <td>{{ $row->qty }}</td>
                        <td><a class="btn btn-ghost" href="{{ route('daily-logs.index', ['user_id' => $row->user_id, 'date' => $to->toDateString()]) }}">دفتر</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">در این بازه رویدادی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h3 style="margin-top:0;">جزئیات</h3>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>کارمند</th>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>تعداد</th>
                    <th>توضیح</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ jalali_date($entry->work_date) }}</td>
                        <td>{{ $entry->user?->name }}</td>
                        <td>{{ $entry->displayTitle() }}</td>
                        <td>{{ $entry->category_name ?: '—' }}</td>
                        <td>{{ $entry->quantity ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($entry->body, 80) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">موردی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links('partials.pagination') }}
    </section>
</div>
@endsection
