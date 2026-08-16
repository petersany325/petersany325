@extends('layouts.app')
@section('title', 'گزارش دفتر روز | '.shop_name())
@section('page_title', 'گزارش دفتر روز')
@section('window_title', 'منوی کارمندان و گزارش روزانه')

@section('content')
@include('reports._print', ['printTitle' => 'گزارش دفتر روز کارمندان'])
@php
    $pct = $stats['slots'] > 0 ? round(($stats['complete'] / $stats['slots']) * 100) : 0;
    $openPanel = collect($employeePanels)->first(fn ($p) => (int) $p['user']->id === (int) $openUserId) ?: ($employeePanels[0] ?? null);
    $filterQs = array_filter([
        'from' => $from->toDateString(),
        'to' => $to->toDateString(),
        'user_id' => $employeeId ?: null,
        'status' => $statusFilter !== 'all' ? $statusFilter : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="daybook daybook-v2">
    <section class="daybook-hero">
        <div class="daybook-hero-copy">
            <p class="daybook-eyebrow">مدیریت و نظارت</p>
            <h2>گزارش دفتر روز کارمندان</h2>
            <p class="daybook-sub">از منوی سمت راست نام کارمند را باز کنید؛ گزارش‌ها به تفکیک روز نمایش داده می‌شود. {{ $exemptNote }}</p>
        </div>
        <div class="daybook-hero-actions no-print">
            <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">ثبت امروز</a>
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات</a>
            @endif
        </div>
    </section>

    <form method="GET" class="panel daybook-toolbar no-print">
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
                    <label>فیلتر کارمند</label>
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

    <section class="daybook-staff-layout">
        <aside class="panel daybook-staff-menu" aria-label="منوی کارمندان">
            <div class="daybook-section-head">
                <div>
                    <h3>کارمندان</h3>
                    <p class="muted">{{ count($employeePanels) }} نفر — روی نام بزنید</p>
                </div>
            </div>
            <nav class="daybook-staff-list">
                @forelse($employeePanels as $panel)
                    @php
                        $u = $panel['user'];
                        $s = $panel['stats'];
                        $isOpen = $openPanel && (int) $openPanel['user']->id === (int) $u->id;
                        $tone = $s['missing'] > 0 ? 'bad' : ($s['partial'] > 0 ? 'warn' : 'ok');
                    @endphp
                    <a class="daybook-staff-item {{ $isOpen ? 'is-active' : '' }} tone-{{ $tone }}"
                       href="{{ route('daily-logs.report', array_merge($filterQs, ['open' => $u->id])) }}">
                        <div class="daybook-staff-name">{{ $u->name }}</div>
                        <div class="daybook-staff-meta">
                            <span>{{ $u->roleLabel() }}</span>
                            <span>{{ $s['entries'] }} ثبت</span>
                        </div>
                        <div class="daybook-staff-badges">
                            @if($s['complete'])<span class="daybook-status ok">{{ $s['complete'] }} تکمیل</span>@endif
                            @if($s['partial'])<span class="daybook-status warn">{{ $s['partial'] }} ناقص</span>@endif
                            @if($s['missing'])<span class="daybook-status bad">{{ $s['missing'] }} غایب</span>@endif
                            @if($s['issues'])<span class="daybook-status warn">{{ $s['issues'] }} پیگیری</span>@endif
                        </div>
                    </a>
                @empty
                    <p class="muted" style="padding:8px 4px;">با این فیلتر کارمندی نیست.</p>
                @endforelse
            </nav>
        </aside>

        <div class="panel daybook-staff-detail">
            @if($openPanel)
                @php $u = $openPanel['user']; $s = $openPanel['stats']; @endphp
                <div class="daybook-section-head">
                    <div>
                        <h3>{{ $u->name }}</h3>
                        <p class="muted">{{ $u->roleLabel() }} · از {{ jalali_date($from) }} تا {{ jalali_date($to) }} · {{ $s['entries'] }} ثبت</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('daily-logs.index', ['user_id' => $u->id, 'date' => $to->toDateString()]) }}">دفتر این نفر</a>
                </div>

                <div class="daybook-day-stack">
                    @forelse($openPanel['days'] as $day)
                        @php
                            $fillLabel = match($day['fill']) {
                                'complete' => 'تکمیل',
                                'partial' => 'ناقص',
                                default => 'بدون ثبت',
                            };
                            $fillClass = match($day['fill']) {
                                'complete' => 'ok',
                                'partial' => 'warn',
                                default => 'bad',
                            };
                        @endphp
                        <details class="daybook-day-card is-{{ $fillClass }}" @if($loop->first) open @endif>
                            <summary>
                                <div class="daybook-day-summary">
                                    <strong dir="ltr">{{ jalali_date($day['date']) }}</strong>
                                    <span class="daybook-status {{ $fillClass }}">{{ $fillLabel }}</span>
                                    <span class="muted">{{ $day['count'] }} / {{ $minEntries }} ثبت</span>
                                    @if($day['minutes'])
                                        <span class="muted">{{ $day['minutes'] }} دقیقه</span>
                                    @endif
                                    @if($day['check'])
                                        <span class="daybook-status {{ $day['check']->status === 'issue' ? 'warn' : 'ok' }}">{{ $day['check']->statusLabel() }}</span>
                                    @else
                                        <span class="muted">چک نشده</span>
                                    @endif
                                </div>
                            </summary>

                            <div class="daybook-day-body">
                                @if($day['entries']->isEmpty())
                                    <p class="muted" style="margin:0;">برای این روز ثبتی نیست.</p>
                                @else
                                    <ul class="daybook-entry-list">
                                        @foreach($day['entries'] as $entry)
                                            <li>
                                                <div class="daybook-entry-title">{{ $entry->displayTitle() }}</div>
                                                <div class="daybook-entry-meta">
                                                    <span dir="ltr">ثبت {{ jalali_like($entry->created_at) }}</span>
                                                    @if($entry->category_name)<span>{{ $entry->category_name }}</span>@endif
                                                    @if($entry->quantity)<span>تعداد: {{ $entry->quantity }}</span>@endif
                                                    @if($entry->minutes)<span>{{ $entry->minutes }} دقیقه</span>@endif
                                                </div>
                                                @if($entry->body)
                                                    <p class="daybook-entry-body">{{ $entry->body }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if($day['check']?->note)
                                    <p class="muted" style="margin:8px 0 0;font-size:11px;">یادداشت مدیر: {{ $day['check']->note }}
                                        @if($day['check']->checker) · {{ $day['check']->checker->name }}@endif
                                    </p>
                                @endif

                                <div class="daybook-check-actions" style="margin-top:10px;">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('daily-logs.index', ['user_id' => $u->id, 'date' => $day['date']]) }}">ویرایش دفتر</a>
                                    @unless($day['check'] && $day['check']->status === 'reviewed')
                                        <form method="POST" action="{{ route('daily-logs.check') }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $u->id }}">
                                            <input type="hidden" name="work_date" value="{{ $day['date'] }}">
                                            <input type="hidden" name="status" value="reviewed">
                                            <input type="hidden" name="open" value="{{ $u->id }}">
                                            <button class="btn btn-secondary btn-sm" type="submit">تأیید بررسی</button>
                                        </form>
                                    @endunless
                                    @unless($day['check'] && $day['check']->status === 'issue')
                                        <form method="POST" action="{{ route('daily-logs.check') }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $u->id }}">
                                            <input type="hidden" name="work_date" value="{{ $day['date'] }}">
                                            <input type="hidden" name="status" value="issue">
                                            <input type="hidden" name="note" value="نیاز به پیگیری ثبت دفتر روز">
                                            <button class="btn btn-ghost btn-sm" type="submit">پیگیری</button>
                                        </form>
                                    @endunless
                                    @if($day['check'])
                                        <form method="POST" action="{{ route('daily-logs.uncheck') }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $u->id }}">
                                            <input type="hidden" name="work_date" value="{{ $day['date'] }}">
                                            <button class="btn btn-ghost btn-sm" type="submit">برداشتن چک</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @empty
                        <p class="muted">در این فیلتر روزی برای نمایش نیست.</p>
                    @endforelse
                </div>
            @else
                <p class="muted">کارمندی برای نمایش انتخاب نشده است.</p>
            @endif
        </div>
    </section>
</div>
@endsection
