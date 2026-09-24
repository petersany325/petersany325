@extends('layouts.app')
@section('title', 'گزارش دفتر روز | '.shop_name())
@section('page_title', 'گزارش دفتر روز')
@section('window_title', 'گزارش دقیق کارمندان و کارآموزان')

@section('content')
@php
    $roleLabel = [
        'admin' => 'مدیر',
        'employee' => 'کارمند',
        'intern' => 'کارآموز',
        'technician' => 'تعمیرکار',
        'accountant' => 'حسابدار',
    ];
@endphp
<div class="daybook">
    <section class="daybook-hero panel">
        <div>
            <h2>گزارش دفتر روز</h2>
            <p class="lead" style="margin:4px 0 0;">خلاصه دقیق به تفکیک نفر، دسته و قبض‌های مرتبط</p>
        </div>
        <div class="daybook-hero-actions">
            <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">ثبت امروز</a>
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات</a>
            @endif
        </div>
    </section>

    <form method="GET" class="panel daybook-toolbar daybook-report-filters">
        <div>
            <label>از تاریخ</label>
            @include('partials.jalali-date', ['name' => 'from', 'value' => $from])
        </div>
        <div>
            <label>تا تاریخ</label>
            @include('partials.jalali-date', ['name' => 'to', 'value' => $to])
        </div>
        <div>
            <label>نفر</label>
            <select name="user_id">
                <option value="">همه</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected((int)$employeeId === (int)$emp->id)>
                        {{ $emp->name }} ({{ $roleLabel[$emp->role] ?? $emp->role }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>نقش</label>
            <select name="role">
                <option value="">همه نقش‌ها</option>
                <option value="employee" @selected($role === 'employee')>کارمند</option>
                <option value="intern" @selected($role === 'intern')>کارآموز</option>
                <option value="technician" @selected($role === 'technician')>تعمیرکار</option>
                <option value="admin" @selected($role === 'admin')>مدیر</option>
            </select>
        </div>
        <div>
            <label>دسته</label>
            <select name="category_id">
                <option value="">همه دسته‌ها</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((int)$categoryId === (int)$cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="daybook-check-field">
            <label style="display:flex;gap:6px;align-items:center;min-height:34px;">
                <input type="checkbox" name="only_tickets" value="1" @checked($onlyTickets)>
                فقط رویدادهای دارای قبض
            </label>
        </div>
        <div class="daybook-filter-actions">
            <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
        </div>
    </form>

    <div class="daybook-stats" style="margin-bottom:10px;">
        <div><span>کل رویداد</span><strong>{{ number_format($totals['count']) }}</strong></div>
        <div><span>جمع تعداد</span><strong>{{ number_format($totals['quantity']) }}</strong></div>
        <div><span>جمع دقایق</span><strong>{{ number_format($totals['minutes']) }}</strong></div>
        <div><span>مرتبط با قبض</span><strong>{{ number_format($totals['tickets']) }}</strong></div>
    </div>

    <section class="panel">
        <h3 style="margin-top:0;">خلاصه به تفکیک نفر</h3>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead>
                <tr>
                    <th>نفر</th>
                    <th>نقش</th>
                    <th>رویداد</th>
                    <th>تعداد</th>
                    <th>دقایق</th>
                    <th>با قبض</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($byEmployee as $row)
                    <tr>
                        <td>{{ $row->user?->name ?: '—' }}</td>
                        <td>{{ $roleLabel[$row->user?->role] ?? ($row->user?->role ?: '—') }}</td>
                        <td>{{ $row->cnt }}</td>
                        <td>{{ $row->qty }}</td>
                        <td>{{ $row->mins }}</td>
                        <td>{{ $row->tickets }}</td>
                        <td><a class="btn btn-ghost" href="{{ route('daily-logs.index', ['user_id' => $row->user_id, 'date' => $to->toDateString()]) }}">دفتر</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">در این بازه رویدادی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h3 style="margin-top:0;">خلاصه به تفکیک دسته</h3>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead><tr><th>دسته</th><th>رویداد</th><th>تعداد</th><th>دقایق</th></tr></thead>
                <tbody>
                @forelse($byCategory as $row)
                    <tr>
                        <td>{{ $row->cat }}</td>
                        <td>{{ $row->cnt }}</td>
                        <td>{{ $row->qty }}</td>
                        <td>{{ $row->mins }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">داده‌ای نیست.</td></tr>
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
                    <th>نفر</th>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>قبض</th>
                    <th>تعداد</th>
                    <th>دقیقه</th>
                    <th>توضیح</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ jalali_date($entry->work_date) }}</td>
                        <td>
                            {{ $entry->user?->name }}
                            <div class="muted" style="font-size:10px;">{{ $roleLabel[$entry->user?->role] ?? '' }}</div>
                        </td>
                        <td>{{ $entry->displayTitle() }}</td>
                        <td>{{ $entry->category_name ?: '—' }}</td>
                        <td>
                            @if($entry->reception_id)
                                <a href="{{ route('receptions.show', $entry->reception_id) }}">{{ $entry->ticketLabel() }}</a>
                                @if($entry->reception?->customer)
                                    <div class="muted" style="font-size:10px;">{{ $entry->reception->customer->name }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $entry->quantity ?: '—' }}</td>
                        <td>{{ $entry->minutes ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($entry->body, 80) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">موردی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links('partials.pagination') }}
    </section>
</div>
@endsection
