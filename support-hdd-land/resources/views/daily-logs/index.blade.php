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
@endphp
<div class="daybook">
    <section class="daybook-hero panel">
        <div>
            <p class="daybook-eyebrow">جایگزین دفتر کاغذی شرکت</p>
            <h2>دفتر روز</h2>
            <p class="lead" style="margin:4px 0 0;">هر رویداد روزانه — مشتری، تلفن، نظافت و … — با تاریخ ثبت می‌شود.</p>
        </div>
        <div class="daybook-hero-actions">
            @if($canManage)
                <a class="btn btn-secondary" href="{{ route('daily-logs.report') }}">گزارش همه</a>
            @endif
            @if(auth()->user()->canAccess('daily_logs.manage'))
                <a class="btn btn-ghost" href="{{ route('daily-logs.settings') }}">تنظیمات (ادمین)</a>
            @endif
        </div>
    </section>

    <section class="daybook-toolbar panel">
        <form method="GET" class="daybook-filters" action="{{ route('daily-logs.index') }}">
            <div class="daybook-nav">
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $prev, 'user_id' => $employee->id]) }}">روز قبل</a>
                @include('partials.jalali-date', ['name' => 'date', 'value' => $dateStr, 'attrs' => 'onchange="this.form.submit()"'])
                <a class="btn btn-ghost" href="{{ route('daily-logs.index', ['date' => $next, 'user_id' => $employee->id]) }}">روز بعد</a>
                @if($dateStr !== $today)
                    <a class="btn btn-secondary" href="{{ route('daily-logs.index', ['date' => $today, 'user_id' => $employee->id]) }}">امروز</a>
                @endif
            </div>
            @if($canManage)
                <div>
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
        <div class="daybook-stats">
            <div><span>تاریخ</span><strong>{{ jalali_date($date) }}</strong></div>
            <div><span>کارمند</span><strong>{{ $employee->name }}</strong></div>
            <div><span>رویداد</span><strong>{{ $summary['count'] }}</strong></div>
            @if($settings['show_quantity'])
                <div><span>تعداد</span><strong>{{ $summary['quantity'] }}</strong></div>
            @endif
        </div>
    </section>

    @if($settings['editable'])
        <section class="panel daybook-compose">
            <h3>ثبت رویداد جدید</h3>
            <form method="POST" action="{{ route('daily-logs.store') }}" class="daybook-form">
                @csrf
                <input type="hidden" name="work_date" value="{{ $dateStr }}">
                <input type="hidden" name="user_id" value="{{ $employee->id }}">

                <div class="daybook-cats" id="daybook-cats">
                    @foreach($categories as $cat)
                        <label class="daybook-cat">
                            <input type="radio" name="daily_log_category_id" value="{{ $cat->id }}" data-ask-qty="{{ $cat->ask_quantity ? '1' : '0' }}" data-name="{{ $cat->name }}">
                            <span class="daybook-cat-mark">{{ $cat->mark }}</span>
                            <span>
                                <strong>{{ $cat->name }}</strong>
                                @if($cat->hint)<small>{{ $cat->hint }}</small>@endif
                            </span>
                        </label>
                    @endforeach
                    <label class="daybook-cat">
                        <input type="radio" name="daily_log_category_id" value="" data-ask-qty="0" data-name="" checked>
                        <span class="daybook-cat-mark">+</span>
                        <span><strong>آزاد</strong><small>بدون دسته از پیش‌فرض</small></span>
                    </label>
                </div>

                <div class="accept-row accept-row-3">
                    <div>
                        <label>عنوان</label>
                        <input type="text" name="title" id="daybook-title" placeholder="مثلاً دو مشتری حضوری">
                    </div>
                    @if($settings['show_quantity'])
                        <div id="daybook-qty-wrap">
                            <label>تعداد</label>
                            <input type="number" name="quantity" min="1" max="9999" placeholder="مثلاً ۲">
                        </div>
                    @endif
                    <div>
                        <label>مدت (دقیقه)</label>
                        <input type="number" name="minutes" min="1" max="1440" placeholder="اختیاری">
                    </div>
                </div>
                <div>
                    <label>توضیح {{ $settings['require_note'] ? '' : '(اختیاری)' }}</label>
                    <textarea name="body" rows="2" placeholder="جزئیات رویداد روز…" {{ $settings['require_note'] ? 'required' : '' }}></textarea>
                </div>
                <div class="actions" style="margin-top:8px;">
                    <button class="btn btn-primary" type="submit">ثبت در دفتر روز</button>
                </div>
            </form>
        </section>
    @else
        <div class="alert alert-error">ثبت برای این تاریخ بسته است (فقط {{ $settings['allow_past_days'] }} روز اخیر برای کارمند مجاز است). ادمین می‌تواند همه تاریخ‌ها را ثبت کند.</div>
    @endif

    <section class="panel">
        <h3 style="margin-top:0;">رویدادهای این روز</h3>
        @forelse($entries as $entry)
            <article class="daybook-entry">
                <div class="daybook-entry-mark">{{ $entry->category?->mark ?: '•' }}</div>
                <div class="daybook-entry-main">
                    <strong>{{ $entry->displayTitle() }}</strong>
                    <div class="muted" style="font-size:11px;">
                        {{ $entry->category_name ?: 'آزاد' }}
                        @if($entry->quantity) · تعداد {{ $entry->quantity }} @endif
                        @if($entry->minutes) · {{ $entry->minutes }} دقیقه @endif
                        · {{ $entry->created_at?->timezone('Asia/Tehran')->format('H:i') }}
                        @if($canManage && $entry->created_by && (int)$entry->created_by !== (int)$entry->user_id)
                            · ثبت توسط {{ $entry->creator?->name }}
                        @endif
                    </div>
                    @if($entry->body)
                        <p style="margin:4px 0 0;font-size:12px;">{{ $entry->body }}</p>
                    @endif
                </div>
                @if($settings['editable'])
                    <form method="POST" action="{{ route('daily-logs.destroy', $entry) }}" data-confirm="این رویداد حذف شود؟">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">حذف</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="lead" style="margin:0;">هنوز رویدادی برای این روز ثبت نشده.</p>
        @endforelse
    </section>
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
