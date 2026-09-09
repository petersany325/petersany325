@extends('layouts.app')
@section('title', 'آپدیت نرم‌افزار | '.shop_name())
@section('page_title', 'آپدیت نرم‌افزار')
@section('window_title', 'بررسی زنده و نصب آپدیت')

@section('content')
<div class="sys-tools upd-panel" data-upd-root
     data-check-url="{{ route('system-tools.updates.check') }}"
     data-poll="{{ (int) $pollSeconds }}">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">کانال آپدیت زنده</p>
            <h2>آپدیت نرم‌افزار</h2>
            <p class="lead">
                وقتی نسخه جدید روی سرور لایسنس منتشر شود، اینجا به‌صورت زنده نمایش داده می‌شود.
                با یک کلیک فایل‌ها نصب و مایگریشن اجرا می‌شود.
            </p>
            <div class="upd-safety">
                <strong>حفاظت داده کاربری (اجباری):</strong>
                آپدیت هرگز <code>.env</code>، پوشه <code>storage/</code> (عکس پیش‌سفارش، آپلودها، نشست)،
                دیتابیس مشتریان/قبض‌ها و <code>vendor/</code> را پاک یا جایگزین نمی‌کند.
                فقط کد برنامه به‌روز می‌شود.
            </div>
        </div>
        <div class="sys-hero-status" data-upd-status-box>
            <div class="sys-status-title">وضعیت</div>
            <ul>
                <li>نسخه فعلی: <strong data-upd-current>{{ $status['current'] ?? '—' }}</strong></li>
                <li>آخرین نسخه: <strong data-upd-latest>{{ $status['latest'] ?? '—' }}</strong></li>
                <li data-upd-message>{{ $status['message'] ?? '' }}</li>
                @if(!empty($status['server']))
                    <li class="muted" style="font-size:12px;">سرور: {{ $status['server'] }}</li>
                @endif
            </ul>
            <div class="upd-live-row">
                <span class="upd-live-dot" data-upd-live-dot title="بررسی زنده"></span>
                <span data-upd-live-label>بررسی زنده هر {{ (int) $pollSeconds }} ثانیه</span>
            </div>
        </div>
    </section>

    <section class="sys-grid">
        <article class="sys-card tone-cache upd-card" data-upd-card>
            <div class="sys-card-mark">زنده</div>
            <h3>بررسی آپدیت</h3>
            <p>اتصال به سرور لایسنس و مقایسه نسخه نصب‌شده با آخرین انتشار.</p>
            <button type="button" class="btn btn-secondary" data-upd-refresh>بررسی همین الان</button>
            <div class="upd-badge" data-upd-badge hidden>آپدیت موجود است</div>
        </article>

        <article class="sys-card tone-db upd-card">
            <div class="sys-card-mark">نصب</div>
            <h3>دریافت و نصب</h3>
            <p>دانلود ZIP، کپی امن فایل‌ها، مایگریشن و پاک‌سازی کش. پوشه storage و vendor دست نخورده می‌ماند.</p>
            <form method="POST" action="{{ route('system-tools.updates.apply') }}" data-upd-apply-form
                  onsubmit="return confirm('آپدیت نصب شود؟ پیشنهاد می‌شود قبل از آپدیت از ابزارهای سیستم بکاپ بگیرید.');">
                @csrf
                <label class="upd-confirm">
                    <input type="checkbox" name="confirm" value="1" required data-upd-confirm>
                    تأیید می‌کنم بکاپ گرفته‌ام / ریسک را می‌پذیرم
                </label>
                <button class="btn btn-primary" type="submit" data-upd-apply-btn {{ empty($status['has_update']) ? 'disabled' : '' }}>
                    نصب آپدیت
                </button>
            </form>
        </article>

        <article class="sys-card tone-cache">
            <div class="sys-card-mark">تغییرات</div>
            <h3>چه چیزی در نسخه جدید است؟</h3>
            <ul class="upd-changelog" data-upd-changelog>
                @forelse(($status['changelog'] ?? []) as $line)
                    <li>{{ $line }}</li>
                @empty
                    <li class="muted">هنوز لیست تغییرات دریافت نشده.</li>
                @endforelse
            </ul>
            @if(!empty($status['released_at']))
                <p class="muted" style="margin-top:8px;font-size:12px;">تاریخ انتشار: <span data-upd-released>{{ $status['released_at'] }}</span></p>
            @endif
        </article>

        @if($isSeller)
            <article class="sys-card tone-db">
                <div class="sys-card-mark">فروشنده</div>
                <h3>انتشار برای مشتریان</h3>
                <p>روی این سرور (فروشنده) می‌توانید ZIP آپدیت را منتشر کنید تا پنل مشتریان ببیند.</p>
                <a class="btn btn-secondary" href="{{ route('licenses.releases') }}">مدیریت انتشار آپدیت</a>
            </article>
        @endif
    </section>

    @if(!empty($lastResult))
        <section class="panel" style="margin-top:16px;">
            <h3 style="margin-top:0;">نتیجه آخرین نصب</h3>
            <p>{{ $lastResult['message'] ?? '' }}</p>
            @if(!empty($lastResult['details']))
                <pre class="upd-log">{{ implode("\n", $lastResult['details']) }}</pre>
            @endif
        </section>
    @endif
</div>

<style>
.upd-live-row{display:flex;align-items:center;gap:8px;margin-top:12px;font-size:12px;color:#475569}
.upd-live-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block;box-shadow:0 0 0 0 rgba(34,197,94,.4)}
.upd-live-dot.is-on{background:#16a34a;animation:updPulse 1.6s ease-out infinite}
.upd-live-dot.is-err{background:#dc2626;animation:none}
@keyframes updPulse{0%{box-shadow:0 0 0 0 rgba(22,163,74,.45)}70%{box-shadow:0 0 0 10px rgba(22,163,74,0)}100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}}
.upd-badge{display:inline-block;margin-top:10px;padding:4px 10px;border:1px solid #f5d59a;background:#fff6e5;color:#92400e;border-radius:6px;font-size:12px;font-weight:700}
.upd-confirm{display:flex;align-items:flex-start;gap:8px;margin:10px 0;font-size:13px;line-height:1.5}
.upd-safety{margin-top:12px;padding:10px 12px;border:1px solid #86efac;background:#f0fdf4;border-radius:8px;font-size:13px;line-height:1.7;color:#166534}
.upd-safety code{direction:ltr;font-size:12px}
.upd-changelog{margin:0;padding-right:18px;line-height:1.7}
.upd-log{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;overflow:auto;max-height:280px;direction:ltr;text-align:left}
.upd-banner{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 14px;margin:0 0 12px;border:1px solid #f5d59a;background:linear-gradient(90deg,#fff8eb,#fff);border-radius:8px}
.upd-banner strong{color:#92400e}
</style>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-upd-root]');
    if (!root) return;
    var url = root.getAttribute('data-check-url');
    var poll = Math.max(15, parseInt(root.getAttribute('data-poll') || '45', 10)) * 1000;
    var dot = root.querySelector('[data-upd-live-dot]');
    var label = root.querySelector('[data-upd-live-label]');
    var badge = root.querySelector('[data-upd-badge]');
    var applyBtn = root.querySelector('[data-upd-apply-btn]');
    var busy = false;

    function paint(data) {
        if (!data) return;
        var cur = root.querySelector('[data-upd-current]');
        var lat = root.querySelector('[data-upd-latest]');
        var msg = root.querySelector('[data-upd-message]');
        var log = root.querySelector('[data-upd-changelog]');
        if (cur) cur.textContent = data.current || '—';
        if (lat) lat.textContent = data.latest || '—';
        if (msg) msg.textContent = data.message || '';
        if (log && Array.isArray(data.changelog)) {
            if (data.changelog.length) {
                log.innerHTML = data.changelog.map(function (l) { return '<li>' + String(l).replace(/</g,'&lt;') + '</li>'; }).join('');
            } else {
                log.innerHTML = '<li class="muted">لیست تغییرات خالی است.</li>';
            }
        }
        var has = !!data.has_update;
        if (badge) badge.hidden = !has;
        if (applyBtn) applyBtn.disabled = !has;
        if (dot) {
            dot.classList.toggle('is-on', !!data.ok);
            dot.classList.toggle('is-err', !data.ok);
        }
        if (label && data.ok) {
            label.textContent = has
                ? ('آپدیت ' + (data.latest || '') + ' آماده است — بررسی زنده فعال')
                : ('به‌روز هستید — بررسی زنده هر ' + (poll/1000) + ' ثانیه');
        }
    }

    function check(force) {
        if (busy) return;
        busy = true;
        var u = url + (force ? '?force=1' : '');
        fetch(u, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(paint)
            .catch(function () {
                if (dot) { dot.classList.add('is-err'); dot.classList.remove('is-on'); }
                if (label) label.textContent = 'خطا در بررسی زنده';
            })
            .finally(function () { busy = false; });
    }

    var btn = root.querySelector('[data-upd-refresh]');
    if (btn) btn.addEventListener('click', function () { check(true); });

    paint(@json($status));
    setInterval(function () { check(false); }, poll);
})();
</script>
@endpush
@endsection
