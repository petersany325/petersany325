@extends('layouts.app')
@section('title', 'گزارش دفتر روز | '.shop_name())
@section('page_title', 'گزارش دفتر روز')
@section('window_title', 'بررسی و چک تکمیل دفتر روزانه')

@section('content')
@php
    $pct = $stats['slots'] > 0 ? round(($stats['complete'] / $stats['slots']) * 100) : 0;
@endphp
<div class="daybook daybook-v2">
    <section class="daybook-hero">
        <div class="daybook-hero-copy">
            <p class="daybook-eyebrow">مدیریت و نظارت</p>
            <h2>گزارش و چک دفتر روز</h2>
            <p class="daybook-sub">تکمیل ثبت کارمندان را ببینید، غایبین را پیدا کنید و هر ردیف را بررسی/تأیید کنید. {{ $exemptNote }}</p>
        </div>
        <div class="daybook-hero-actions">
            <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">ثبت امروز</a>
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات</a>
            @endif
        </div>
    </section>

    <form method="GET" class="panel daybook-toolbar">
        <div class="daybook-filters" style="width:100%;">
            <div class="daybook-nav">
                <div class="daybook-date-field">
                    <label>از تاریخ</label>
                    @include('partials.jalali-date', ['name' => 'from', 'value' => $from->toDateString()])
                </div>
                <div class="daybook-date-field">
                    <label>تا تاریخ</label>
                    @include('partials.jalali-date', ['name' => 'to', 'value' => $to->toDateString()])
                </div>
                <div class="daybook-emp-field">
                    <label>کارمند</label>
                    <select name="user_id">
                        <option value="">همه</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((int)$employeeId === (int)$emp->id)>{{ $emp->name }} ({{ $emp->roleLabel() }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="daybook-emp-field">
                    <label>وضعیت ثبت</label>
                    <select name="status">
                        <option value="all" @selected($statusFilter === 'all')>همه</option>
                        <option value="missing" @selected($statusFilter === 'missing')>بدون ثبت</option>
                        <option value="partial" @selected($statusFilter === 'partial')>ناقص</option>
                        <option value="complete" @selected($statusFilter === 'complete')>تکمیل</option>
                        <option value="unchecked" @selected($statusFilter === 'unchecked')>بررسی‌نشده</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
            </div>
        </div>
    </form>

    <section class="daybook-kpi-grid">
        <div class="daybook-kpi tone-teal">
            <span>درصد تکمیل</span>
            <strong>{{ $pct }}٪</strong>
            <small>حداقل {{ $minEntries }} ثبت در روز کاری</small>
        </div>
        <div class="daybook-kpi tone-green">
            <span>تکمیل‌شده</span>
            <strong>{{ $stats['complete'] }}</strong>
            <small>از {{ $stats['slots'] }} جایگاه</small>
        </div>
        <div class="daybook-kpi tone-amber">
            <span>ناقص</span>
            <strong>{{ $stats['partial'] }}</strong>
            <small>کمتر از حداقل</small>
        </div>
        <div class="daybook-kpi tone-rose">
            <span>بدون ثبت</span>
            <strong>{{ $stats['missing'] }}</strong>
            <small>{{ $workDaysCount }} روز کاری در بازه</small>
        </div>
        <div class="daybook-kpi tone-blue">
            <span>بررسی‌شده توسط مدیر</span>
            <strong>{{ $stats['checked'] }}</strong>
            <small>{{ $stats['issues'] }} مورد پیگیری</small>
        </div>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>چک‌لیست تکمیل دفتر روز</h3>
                <p class="muted">هر ردیف = یک نفر در یک روز کاری. می‌توانید بررسی کنید یا پیگیری بگذارید.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data compact-table daybook-check-table">
                <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>کارمند</th>
                    <th>نقش</th>
                    <th>ثبت‌ها</th>
                    <th>وضعیت</th>
                    <th>بررسی مدیر</th>
                    <th>اقدام</th>
                </tr>
                </thead>
                <tbody>
                @forelse($checklist as $row)
                    @php
                        $fillLabel = match($row['fill']) {
                            'complete' => 'تکمیل',
                            'partial' => 'ناقص',
                            default => 'بدون ثبت',
                        };
                        $fillClass = match($row['fill']) {
                            'complete' => 'ok',
                            'partial' => 'warn',
                            default => 'bad',
                        };
                    @endphp
                    <tr class="daybook-check-row is-{{ $fillClass }}">
                        <td dir="ltr">{{ jalali_date($row['date']) }}</td>
                        <td>{{ $row['user']->name }}</td>
                        <td>{{ $row['user']->roleLabel() }}</td>
                        <td>
                            <strong>{{ $row['count'] }}</strong>
                            <span class="muted">/ {{ $minEntries }}</span>
                            @if($row['minutes'])
                                <div class="muted" style="font-size:10px;">{{ $row['minutes'] }} دقیقه</div>
                            @endif
                        </td>
                        <td><span class="daybook-status {{ $fillClass }}">{{ $fillLabel }}</span></td>
                        <td>
                            @if($row['check'])
                                <span class="daybook-status {{ $row['check']->status === 'issue' ? 'warn' : 'ok' }}">{{ $row['check']->statusLabel() }}</span>
                                <div class="muted" style="font-size:10px;">
                                    {{ $row['check']->checker?->name }}
                                    · {{ $row['check']->checked_at ? jalali_like($row['check']->checked_at) : '' }}
                                </div>
                                @if($row['check']->note)
                                    <div class="muted" style="font-size:10px;">{{ $row['check']->note }}</div>
                                @endif
                            @else
                                <span class="muted">هنوز چک نشده</span>
                            @endif
                        </td>
                        <td>
                            <div class="daybook-check-actions">
                                <a class="btn btn-ghost btn-sm" href="{{ route('daily-logs.index', ['user_id' => $row['user']->id, 'date' => $row['date']]) }}">دفتر</a>
                                @unless($row['check'] && $row['check']->status === 'reviewed')
                                    <form method="POST" action="{{ route('daily-logs.check') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                                        <input type="hidden" name="work_date" value="{{ $row['date'] }}">
                                        <input type="hidden" name="status" value="reviewed">
                                        <button class="btn btn-secondary btn-sm" type="submit">تأیید بررسی</button>
                                    </form>
                                @endunless
                                @unless($row['check'] && $row['check']->status === 'issue')
                                    <form method="POST" action="{{ route('daily-logs.check') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                                        <input type="hidden" name="work_date" value="{{ $row['date'] }}">
                                        <input type="hidden" name="status" value="issue">
                                        <input type="hidden" name="note" value="نیاز به پیگیری ثبت دفتر روز">
                                        <button class="btn btn-ghost btn-sm" type="submit">پیگیری</button>
                                    </form>
                                @endunless
                                @if($row['check'])
                                    <form method="POST" action="{{ route('daily-logs.uncheck') }}">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                                        <input type="hidden" name="work_date" value="{{ $row['date'] }}">
                                        <button class="btn btn-ghost btn-sm" type="submit">برداشتن چک</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">در این فیلتر موردی برای چک نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>خلاصه به تفکیک کارمند</h3>
                <p class="muted">از {{ jalali_date($from) }} تا {{ jalali_date($to) }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data compact-table">
                <thead><tr><th>کارمند</th><th>تعداد رویداد</th><th>جمع تعداد</th><th></th></tr></thead>
                <tbody>
                @forelse($byEmployee as $row)
                    <tr>
                        <td>{{ $row->user?->name ?: '—' }}</td>
                        <td>{{ $row->cnt }}</td>
                        <td>{{ $row->qty }}</td>
                        <td><a class="btn btn-ghost btn-sm" href="{{ route('daily-logs.index', ['user_id' => $row->user_id, 'date' => $to->toDateString()]) }}">دفتر</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">در این بازه رویدادی نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>جزئیات رویدادها</h3>
                <p class="muted">فهرست رویدادهای فیلترشده</p>
            </div>
        </div>
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
                        <td dir="ltr">{{ jalali_date($entry->work_date) }}</td>
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
