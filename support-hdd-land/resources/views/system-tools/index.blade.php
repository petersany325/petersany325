@extends('layouts.app')
@section('title', 'ابزارهای سیستم | سرزمین هارد')
@section('page_title', 'ابزارهای سیستم')
@section('window_title', 'نگهداری، کش و تعمیر دیتابیس')

@section('content')
<div class="sys-tools">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">نگهداری سیستم</p>
            <h2>ابزارهای سیستم</h2>
            <p class="lead">پاک‌سازی کش سایت، بررسی سلامت، تعمیر و بازسازی دیتابیس آسیب‌دیده — بدون حذف داده.</p>
        </div>
        <div class="sys-hero-status">
            <div class="sys-status-title">وضعیت فعلی</div>
            <ul>
                @foreach($snapshot as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="sys-grid">
        <article class="sys-card tone-cache">
            <div class="sys-card-mark">کش</div>
            <h3>پاک‌سازی کش سایت</h3>
            <p>کش برنامه، تنظیمات، مسیرها و ویوها را خالی می‌کند. بعد از آپدیت کد یا خطای عجیب مفید است.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="clear_cache">
                <button class="btn btn-primary" type="submit">پاک‌سازی کش</button>
            </form>
        </article>

        <article class="sys-card tone-cache">
            <div class="sys-card-mark">بهینه</div>
            <h3>بازسازی کش (بهینه‌سازی)</h3>
            <p>ابتدا کش‌ها را پاک می‌کند، سپس config/route/view را دوباره می‌سازد برای سرعت بیشتر.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="rebuild_cache">
                <button class="btn btn-secondary" type="submit">بازسازی کش</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">سلامت</div>
            <h3>بررسی سلامت دیتابیس</h3>
            <p>اتصال، تعداد جداول، CHECK TABLE و مایگریشن‌های معوق را گزارش می‌دهد.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="db_health">
                <button class="btn btn-secondary" type="submit">بررسی سلامت</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">تعمیر</div>
            <h3>تعمیر دیتابیس</h3>
            <p>روی همه جداول MySQL/MariaDB دستور REPAIR TABLE اجرا می‌کند. داده حذف نمی‌شود.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" onsubmit="return confirm('تعمیر جداول دیتابیس اجرا شود؟');">
                @csrf
                <input type="hidden" name="action" value="db_repair">
                <button class="btn btn-secondary" type="submit">تعمیر جداول</button>
            </form>
        </article>

        <article class="sys-card tone-db">
            <div class="sys-card-mark">بهینه</div>
            <h3>بهینه‌سازی جداول</h3>
            <p>OPTIMIZE TABLE برای کاهش فضای هدررفته و بهبود عملکرد جداول.</p>
            <form method="POST" action="{{ route('system-tools.run') }}">
                @csrf
                <input type="hidden" name="action" value="db_optimize">
                <button class="btn btn-ghost" type="submit">بهینه‌سازی</button>
            </form>
        </article>

        <article class="sys-card tone-danger">
            <div class="sys-card-mark">بازسازی</div>
            <h3>بازسازی دیتابیس آسیب‌دیده</h3>
            <p>بررسی → تعمیر → بهینه‌سازی → مایگریشن معوق → پاک‌سازی کش. بدون حذف یا خالی‌کردن دیتابیس.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" onsubmit="return confirm('بازسازی دیتابیس آسیب‌دیده شروع شود؟ این کار داده را حذف نمی‌کند.');">
                @csrf
                <input type="hidden" name="action" value="db_rebuild">
                <button class="btn btn-primary" type="submit">شروع بازسازی</button>
            </form>
        </article>

        <article class="sys-card tone-migrate">
            <div class="sys-card-mark">مایگر</div>
            <h3>اجرای مایگریشن‌های معوق</h3>
            <p>فقط مایگریشن‌های اجرا‌نشده را اعمال می‌کند. برای به‌روزرسانی ساختار بعد از آپلود کد.</p>
            <form method="POST" action="{{ route('system-tools.run') }}" onsubmit="return confirm('مایگریشن‌های معوق اجرا شوند؟');">
                @csrf
                <input type="hidden" name="action" value="migrate">
                <button class="btn btn-secondary" type="submit">اجرای مایگریشن</button>
            </form>
        </article>
    </section>

    @if(!empty($lastResult))
        <section class="panel sys-result {{ !empty($lastResult['ok']) ? 'is-ok' : 'is-bad' }}">
            <h3>نتیجه آخرین عملیات</h3>
            <p class="lead" style="margin:0 0 8px;">{{ $lastResult['message'] ?? '' }}</p>
            @if(!empty($lastResult['details']))
                <div class="sys-log">
                    @foreach($lastResult['details'] as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <p class="muted sys-note">این ابزارها داده را پاک نمی‌کنند. حذف کامل دیتابیس یا migrate:fresh اینجا وجود ندارد.</p>
</div>
@endsection
