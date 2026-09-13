@extends('layouts.app')
@section('title', 'پرتال کارآموز | '.shop_name())
@section('page_title', 'پرتال کارآموز')
@section('window_title', 'خدمات شرکت و دفتر روز')

@section('content')
<div class="intern-portal">
    <section class="panel daybook-hero">
        <div>
            <p class="daybook-eyebrow">پرتال کارآموز — {{ shop_name() }}</p>
            <h2 style="margin:0;">سلام {{ $user->name }}</h2>
            <p class="lead" style="margin:4px 0 0;">
                @if($intern)
                    دوره: {{ jalali_date($intern->start_date) }} تا {{ jalali_date($intern->end_date) }}
                    @if($intern->department) · {{ $intern->department }}@endif
                @else
                    خدمات تعریف‌شده شرکت را ثبت کنید.
                @endif
            </p>
        </div>
        <div class="daybook-hero-actions">
            @if($canLog)
                <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">دفتر روز کامل</a>
            @endif
            <a class="btn btn-ghost" href="{{ route('profile.edit') }}">پروفایل</a>
        </div>
    </section>

    <section class="panel" style="margin-bottom:12px;">
        <div class="daybook-stats">
            <div><span>امروز</span><strong>{{ jalali_date($date) }}</strong></div>
            <div><span>ثبت امروز</span><strong>{{ $summary['count'] }}</strong></div>
            @if($showQuantity)
                <div><span>تعداد</span><strong>{{ $summary['quantity'] }}</strong></div>
            @endif
            <div><span>خدمات فعال</span><strong>{{ $categories->count() }}</strong></div>
        </div>
    </section>

    @if($canLog)
        <section class="panel daybook-compose">
            <h3 style="margin-top:0;">ثبت خدمت انجام‌شده</h3>
            <p class="muted" style="margin-top:0;">خدمات را مدیر از تنظیمات دفتر روز تعریف می‌کند؛ شما انجام می‌دهید و اینجا ثبت می‌کنید.</p>
            <form method="POST" action="{{ route('intern.log') }}" class="daybook-form">
                @csrf
                <div class="daybook-cats" id="intern-cats">
                    @forelse($categories as $cat)
                        <label class="daybook-cat">
                            <input type="radio" name="daily_log_category_id" value="{{ $cat->id }}" data-ask-qty="{{ $cat->ask_quantity ? '1' : '0' }}" required>
                            <span class="daybook-cat-mark">{{ $cat->mark }}</span>
                            <span>
                                <strong>{{ $cat->name }}</strong>
                                @if($cat->hint)<small>{{ $cat->hint }}</small>@endif
                            </span>
                        </label>
                    @empty
                        <p class="lead">هنوز خدمتی تعریف نشده. از مدیر بخواهید در «تنظیمات دفتر روز» دسته اضافه کند.</p>
                    @endforelse
                </div>
                @if($categories->isNotEmpty())
                    <div class="accept-row accept-row-2" style="margin-top:10px;">
                        <div id="intern-qty-wrap" class="hidden">
                            <label>تعداد</label>
                            <input type="number" name="quantity" min="0" max="9999" value="1">
                        </div>
                        <div>
                            <label>مدت (دقیقه — اختیاری)</label>
                            <input type="number" name="minutes" min="0" max="1440" placeholder="مثلاً ۳۰">
                        </div>
                        <div style="grid-column:1/-1">
                            <label>توضیح {{ $requireNote ? '(الزامی)' : '(اختیاری)' }}</label>
                            <textarea name="body" rows="2" placeholder="شرح کار انجام‌شده…" {{ $requireNote ? 'required' : '' }}></textarea>
                        </div>
                    </div>
                    <div class="actions" style="margin-top:8px;">
                        <button class="btn btn-primary" type="submit">ثبت در دفتر روز</button>
                    </div>
                @endif
            </form>
        </section>
    @else
        <section class="panel">
            <p class="lead">دسترسی دفتر روزانه برای شما فعال نیست. از مدیر بخواهید در کارتابل کارآموز، دسترسی «دفتر روزانه» را روشن کند.</p>
        </section>
    @endif

    <section class="panel" style="margin-top:12px;">
        <h3 style="margin-top:0;">ثبت‌های امروز</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>خدمت</th>
                    <th>توضیح</th>
                    <th>تعداد</th>
                    <th>زمان</th>
                </tr>
                </thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->displayTitle() }}</td>
                        <td>{{ $entry->body ?: '—' }}</td>
                        <td>{{ $entry->quantity ?? '—' }}</td>
                        <td>{{ jalali_like($entry->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">هنوز چیزی برای امروز ثبت نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var cats = document.getElementById('intern-cats');
    var qty = document.getElementById('intern-qty-wrap');
    if (!cats || !qty) return;
    cats.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.name !== 'daily_log_category_id') return;
        qty.classList.toggle('hidden', input.getAttribute('data-ask-qty') !== '1');
    });
})();
</script>
@endpush
