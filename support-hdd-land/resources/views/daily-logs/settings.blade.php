@extends('layouts.app')
@section('title', 'تنظیمات دفتر روز | '.shop_name())
@section('page_title', 'تنظیمات دفتر روز')
@section('window_title', 'قوانین چک و دسته‌های دفتر روزانه')

@section('content')
<div class="daybook daybook-v2">
    <section class="daybook-hero">
        <div class="daybook-hero-copy">
            <p class="daybook-eyebrow">فقط ادمین</p>
            <h2>تنظیمات دفتر روز</h2>
            <p class="daybook-sub">حداقل ثبت روزانه، تعطیلی‌ها، و دسته‌های رویداد را اینجا مدیریت کنید.</p>
        </div>
        <div class="daybook-hero-actions">
            <a class="btn btn-secondary" href="{{ route('daily-logs.index') }}">بازگشت به دفتر</a>
            <a class="btn btn-ghost" href="{{ route('daily-logs.report') }}">گزارش و چک</a>
        </div>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>قوانین ثبت و چک</h3>
                <p class="muted">این قوانین مبنای گزارش «تکمیل / ناقص / بدون ثبت» هستند.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('daily-logs.settings.save') }}" class="accept-row accept-row-3" style="align-items:end;">
            @csrf
            <div>
                <label>اجازه ثبت روزهای گذشته (برای کارمند)</label>
                <input type="number" name="allow_past_days" min="0" max="60" value="{{ $options['allow_past_days'] }}">
            </div>
            <div>
                <label>حداقل ثبت در هر روز کاری</label>
                <input type="number" name="min_entries" min="1" max="20" value="{{ $options['min_entries'] }}">
            </div>
            <div>
                @include('partials.toggle', [
                    'name' => 'require_note',
                    'label' => 'توضیح رویداد الزامی باشد',
                    'checked' => $options['require_note'],
                ])
            </div>
            <div>
                @include('partials.toggle', [
                    'name' => 'show_quantity',
                    'label' => 'نمایش فیلد تعداد',
                    'checked' => $options['show_quantity'],
                ])
            </div>
            <div>
                @include('partials.toggle', [
                    'name' => 'skip_fridays',
                    'label' => 'جمعه‌ها از چک تکمیل معاف باشند',
                    'checked' => $options['skip_fridays'],
                ])
            </div>
            <div style="grid-column:1/-1;">
                <label>تعطیلی‌های اعلامی فروشگاه (هر تاریخ در یک خط یا با ویرگول — شمسی یا میلادی)</label>
                <textarea name="closed_dates" rows="3" placeholder="مثلاً&#10;1404/05/25&#10;1404/06/01">{{ $options['closed_dates_display'] ?? $options['closed_dates'] }}</textarea>
            </div>
            <div class="actions" style="grid-column:1/-1;margin:0;">
                <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
            </div>
        </form>
        <p class="hint">ادمین همیشه می‌تواند برای هر تاریخ و هر کارمند ثبت کند. روزهای معاف در گزارش چک نمی‌آیند.</p>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>دسته جدید</h3>
                <p class="muted">دسته‌ها در فرم ثبت دفتر روز نمایش داده می‌شوند.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('daily-logs.categories.store') }}" class="accept-row accept-row-4" style="align-items:end;">
            @csrf
            <div>
                <label>نام</label>
                <input type="text" name="name" required placeholder="مثلاً نظافت">
            </div>
            <div>
                <label>راهنما</label>
                <input type="text" name="hint" placeholder="توضیح کوتاه">
            </div>
            <div>
                <label>علامت</label>
                <input type="text" name="mark" maxlength="4" placeholder="ن">
            </div>
            <div>
                <label>ترتیب</label>
                <input type="number" name="sort_order" value="100" min="0">
            </div>
            <div>
                @include('partials.toggle', ['name' => 'ask_quantity', 'label' => 'درخواست تعداد', 'checked' => false])
            </div>
            <div>
                @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => true])
            </div>
            <div class="actions" style="margin:0;">
                <button class="btn btn-primary" type="submit">افزودن دسته</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="daybook-section-head">
            <div>
                <h3>دسته‌های موجود</h3>
            </div>
        </div>
        @foreach($categories as $cat)
            <form method="POST" action="{{ route('daily-logs.categories.update', $cat) }}" class="daybook-cat-edit">
                @csrf
                @method('PUT')
                <div class="accept-row accept-row-4" style="align-items:end;">
                    <div>
                        <label>نام</label>
                        <input type="text" name="name" value="{{ $cat->name }}" required>
                    </div>
                    <div>
                        <label>راهنما</label>
                        <input type="text" name="hint" value="{{ $cat->hint }}">
                    </div>
                    <div>
                        <label>علامت</label>
                        <input type="text" name="mark" value="{{ $cat->mark }}" maxlength="4">
                    </div>
                    <div>
                        <label>ترتیب</label>
                        <input type="number" name="sort_order" value="{{ $cat->sort_order }}" min="0">
                    </div>
                    <div>
                        @include('partials.toggle', ['name' => 'ask_quantity', 'label' => 'تعداد', 'checked' => $cat->ask_quantity])
                    </div>
                    <div>
                        @include('partials.toggle', ['name' => 'is_active', 'label' => 'فعال', 'checked' => $cat->is_active])
                    </div>
                    <div class="actions" style="margin:0;display:flex;gap:4px;">
                        <button class="btn btn-secondary" type="submit">ذخیره</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('daily-logs.categories.destroy', $cat) }}" style="margin:-4px 0 10px;" data-confirm="دسته «{{ $cat->name }}» حذف شود؟">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">حذف دسته</button>
            </form>
        @endforeach
    </section>
</div>
@endsection
