@extends('layouts.app')
@section('title', 'دفتر روز | '.shop_name())
@section('page_title', 'دفتر روز')
@section('window_title', 'ثبت کار و رویدادهای روزانه')

@section('content')
@php
    $dateStr = $date->toDateString();
    $prev = $date->copy()->subDay()->toDateString();
    $next = $date->copy()->addDay()->toDateString();
    $today = now('Asia/Tehran')->toDateString();
    $isToday = $dateStr === $today;
    $minutes = (int) ($summary['minutes'] ?? 0);
    $hoursLabel = $minutes > 0 ? (floor($minutes / 60) ? floor($minutes / 60).'س '.($minutes % 60).'د' : $minutes.' دقیقه') : '—';
@endphp
<div class="daybook daybook-v2">
    <section class="daybook-hero">
        <div class="daybook-hero-copy">
            <p class="daybook-eyebrow">کارتابل عملیات روزانه</p>
            <h2>دفتر روز</h2>
            <p class="daybook-sub">رویدادهای کاری امروز را با دسته، تعداد و مدت ثبت کنید؛ گزارش برای مدیر آماده می‌شود.</p>
        </div>
        <div class="daybook-hero-actions">
            @if($canManage)
                <a class="btn btn-secondary" href="{{ route('daily-logs.report') }}">گزارش همه</a>
            @endif
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات</a>
            @endif
        </div>
    </section>

    <section class="daybook-kpi-grid">
        <div class="daybook-kpi tone-slate">
            <span>تاریخ</span>
            <strong dir="ltr">{{ jalali_date($date) }}</strong>
            <small>{{ $isToday ? 'امروز' : 'روز انتخاب‌شده' }}</small>
        </div>
        <div class="daybook-kpi tone-teal">
            <span>کارمند</span>
            <strong>{{ $employee->name }}</strong>
            <small>{{ $employee->roleLabel() ?? ($employee->role ?? '—') }}</small>
        </div>
        <div class="daybook-kpi tone-blue">
            <span>رویداد امروز</span>
            <strong>{{ $summary['count'] }}</strong>
            <small>ثبت‌شده در دفتر</small>
        </div>
        @if($settings['show_quantity'])
            <div class="daybook-kpi tone-amber">
                <span>جمع تعداد</span>
                <strong>{{ $summary['quantity'] }}</strong>
                <small>بر اساس فیلد تعداد</small>
            </div>
        @endif
        <div class="daybook-kpi tone-green">
            <span>مدت کل</span>
            <strong>{{ $hoursLabel }}</strong>
            <small>{{ $minutes ? $minutes.' دقیقه' : 'بدون ثبت مدت' }}</small>
        </div>
    </section>

    <section class="daybook-toolbar panel">
        <form method="GET" class="daybook-filters" action="{{ route('daily-logs.index') }}" id="daybook-filter-form">
            <div class="daybook-nav">
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $prev, 'user_id' => $employee->id]) }}">← روز قبل</a>
                <div class="daybook-date-field">
                    <label>تاریخ</label>
                    @include('partials.jalali-date', [
                        'name' => 'date',
                        'value' => $dateStr,
                        'attrs' => 'onchange="this.form.submit()"',
                    ])
                </div>
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $next, 'user_id' => $employee->id]) }}">روز بعد →</a>
                @unless($isToday)
                    <a class="btn btn-secondary" href="{{ route('daily-logs.index', ['date' => $today, 'user_id' => $employee->id]) }}">برو به امروز</a>
                @endunless
            </div>
            @if($canManage)
                <div class="daybook-emp-field">
                    <label>کارمند</label>
                    <select name="user_id" onchange="this.form.submit()">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((int)$employee->id === (int)$emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="user_id" value="{{ $employee->id }}">
            @endif
        </form>
    </section>

    <div class="daybook-desk">
        <div class="daybook-desk-main">
            @if($settings['editable'])
                <section class="panel daybook-compose">
                    <div class="daybook-section-head">
                        <div>
                            <h3>ثبت رویداد جدید</h3>
                            <p class="muted">دسته را انتخاب کنید، بعد عنوان و جزئیات را بنویسید.</p>
                        </div>
                        <span class="daybook-badge">{{ $isToday ? 'ثبت امروز' : 'ثبت تاریخ انتخابی' }}</span>
                    </div>
                    <form method="POST" action="{{ route('daily-logs.store') }}" class="daybook-form">
                        @csrf
                        <input type="hidden" name="work_date" value="{{ $dateStr }}">
                        <input type="hidden" name="user_id" value="{{ $employee->id }}">

                        <div class="daybook-cats" id="daybook-cats">
                            @foreach($categories as $cat)
                                <label class="daybook-cat">
                                    <input type="radio" name="daily_log_category_id" value="{{ $cat->id }}" data-ask-qty="{{ $cat->ask_quantity ? '1' : '0' }}" data-name="{{ $cat->name }}">
                                    <span class="daybook-cat-mark">{{ $cat->mark }}</span>
                                    <span class="daybook-cat-text">
                                        <strong>{{ $cat->name }}</strong>
                                        @if($cat->hint)<small>{{ $cat->hint }}</small>@endif
                                    </span>
                                </label>
                            @endforeach
                            <label class="daybook-cat daybook-cat-free is-on">
                                <input type="radio" name="daily_log_category_id" value="" data-ask-qty="0" data-name="" checked>
                                <span class="daybook-cat-mark">+</span>
                                <span class="daybook-cat-text"><strong>آزاد</strong><small>بدون دسته از پیش‌فرض</small></span>
                            </label>
                        </div>

                        <div class="daybook-fields">
                            <div class="daybook-field grow">
                                <label>عنوان</label>
                                <input type="text" name="title" id="daybook-title" placeholder="مثلاً دو مشتری حضوری" maxlength="120">
                            </div>
                            @if($settings['show_quantity'])
                                <div class="daybook-field" id="daybook-qty-wrap">
                                    <label>تعداد</label>
                                    <input type="number" name="quantity" min="1" max="9999" placeholder="۲">
                                </div>
                            @endif
                            <div class="daybook-field">
                                <label>مدت (دقیقه)</label>
                                <input type="number" name="minutes" min="1" max="1440" placeholder="۳۰">
                            </div>
                        </div>
                        <div class="daybook-field">
                            <label>توضیح {{ $settings['require_note'] ? '' : '(اختیاری)' }}</label>
                            <textarea name="body" rows="3" placeholder="جزئیات رویداد روز…" {{ $settings['require_note'] ? 'required' : '' }}></textarea>
                        </div>
                        <div class="daybook-compose-actions">
                            <button class="btn btn-primary" type="submit">ثبت در دفتر روز</button>
                            <span class="muted">بعد از ثبت، در تایم‌لاین همین صفحه دیده می‌شود.</span>
                        </div>
                    </form>
                </section>
            @else
                <div class="alert alert-error">ثبت برای این تاریخ بسته است (فقط {{ $settings['allow_past_days'] }} روز اخیر برای کارمند مجاز است). ادمین می‌تواند همه تاریخ‌ها را ثبت کند.</div>
            @endif

            <section class="panel daybook-timeline-panel">
                <div class="daybook-section-head">
                    <div>
                        <h3>تایم‌لاین روز</h3>
                        <p class="muted">{{ $summary['count'] }} رویداد برای {{ jalali_date($date) }}</p>
                    </div>
                </div>

                @forelse($entries as $entry)
                    <article class="daybook-entry">
                        <div class="daybook-entry-rail">
                            <div class="daybook-entry-mark">{{ $entry->category?->mark ?: '•' }}</div>
                            <div class="daybook-entry-line"></div>
                        </div>
                        <div class="daybook-entry-body">
                            <div class="daybook-entry-top">
                                <strong>{{ $entry->displayTitle() }}</strong>
                                <time dir="ltr">{{ $entry->created_at?->timezone('Asia/Tehran')->format('H:i') }}</time>
                            </div>
                            <div class="daybook-entry-meta">
                                <span class="daybook-chip">{{ $entry->category_name ?: 'آزاد' }}</span>
                                @if($entry->quantity)<span class="daybook-chip">تعداد {{ $entry->quantity }}</span>@endif
                                @if($entry->minutes)<span class="daybook-chip">{{ $entry->minutes }} دقیقه</span>@endif
                                @if($canManage && $entry->created_by && (int)$entry->created_by !== (int)$entry->user_id)
                                    <span class="daybook-chip soft">ثبت توسط {{ $entry->creator?->name }}</span>
                                @endif
                            </div>
                            @if($entry->body)
                                <p class="daybook-entry-note">{{ $entry->body }}</p>
                            @endif
                        </div>
                        @if($settings['editable'])
                            <form method="POST" action="{{ route('daily-logs.destroy', $entry) }}" data-confirm="این رویداد حذف شود؟" class="daybook-entry-actions">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm" type="submit">حذف</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="daybook-empty">
                        <strong>هنوز رویدادی ثبت نشده</strong>
                        <p>اولین کار یا تماس امروز را از فرم بالا ثبت کنید.</p>
                    </div>
                @endforelse
            </section>
        </div>

        <aside class="daybook-side">
            <section class="panel daybook-guide">
                <h3>راهنمای سریع</h3>
                <ol>
                    <li>دسته مناسب را انتخاب کنید.</li>
                    <li>عنوان کوتاه و واضح بنویسید.</li>
                    <li>تعداد و مدت را در صورت نیاز پر کنید.</li>
                    <li>اگر توضیح لازم است، جزئیات را بنویسید.</li>
                </ol>
            </section>
            <section class="panel daybook-side-stats">
                <h3>خلاصه همین روز</h3>
                <div class="daybook-side-row"><span>رویداد</span><strong>{{ $summary['count'] }}</strong></div>
                @if($settings['show_quantity'])
                    <div class="daybook-side-row"><span>جمع تعداد</span><strong>{{ $summary['quantity'] }}</strong></div>
                @endif
                <div class="daybook-side-row"><span>مدت</span><strong>{{ $hoursLabel }}</strong></div>
                <div class="daybook-side-row"><span>وضعیت ثبت</span><strong>{{ $settings['editable'] ? 'باز' : 'بسته' }}</strong></div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var cats = document.getElementById('daybook-cats');
    var title = document.getElementById('daybook-title');
    if (!cats || !title) return;
    cats.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.name !== 'daily_log_category_id') return;
        var name = input.getAttribute('data-name') || '';
        if (name && !title.value) title.placeholder = name;
        document.querySelectorAll('.daybook-cat').forEach(function (el) {
            el.classList.toggle('is-on', el.contains(input) && input.checked);
        });
    });
})();
</script>
@endpush
