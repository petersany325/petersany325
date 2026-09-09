@extends('layouts.app')
@section('title', 'آپدیت نرم‌افزار | '.shop_name())
@section('page_title', 'آپدیت نرم‌افزار')
@section('window_title', 'بررسی لایسنس و نصب آپدیت')

@php
    $lic = $status['license'] ?? [];
    $licValid = !empty($lic['valid']);
    $canUpdate = !empty($lic['can_update']);
@endphp

@section('content')
<div class="sys-tools upd-panel" data-upd-root
     data-check-url="{{ route('system-tools.updates.check') }}"
     data-poll="{{ (int) $pollSeconds }}">
    <section class="sys-hero panel">
        <div class="sys-hero-copy">
            <p class="sys-eyebrow">کانال آپدیت زنده</p>
            <h2>آپدیت نرم‌افزار</h2>
            <p class="lead">
                قبل از هر بررسی/نصب، اعتبار لایسنس چک می‌شود و گزارش واضح نمایش داده می‌شود.
                فقط با لایسنس معتبر می‌توانید نسخه جدید را دریافت کنید.
            </p>
        </div>
        <div class="sys-hero-status" data-upd-status-box>
            <div class="sys-status-title">وضعیت نسخه</div>
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

    <section class="upd-license panel" data-upd-license
             data-valid="{{ $licValid ? '1' : '0' }}"
             data-can-update="{{ $canUpdate ? '1' : '0' }}">
        <div class="upd-license-head">
            <div>
                <div class="sys-status-title">گزارش لایسنس</div>
                <h3 style="margin:4px 0 0;" data-upd-lic-label>{{ $lic['label'] ?? '—' }}</h3>
            </div>
            <span class="upd-lic-pill {{ $licValid ? 'is-ok' : 'is-bad' }}" data-upd-lic-pill>
                {{ $licValid ? 'معتبر' : 'نامعتبر' }}
            </span>
        </div>
        <p class="upd-lic-msg" data-upd-lic-msg>{{ $lic['message'] ?? 'وضعیت لایسنس هنوز بررسی نشده.' }}</p>
        <ul class="upd-lic-meta">
            <li>کد: <strong data-upd-lic-code>{{ $lic['code'] ?? '—' }}</strong></li>
            <li>پلن: <strong data-upd-lic-plan>{{ $lic['plan'] ?? '—' }}</strong></li>
            <li>دامنه: <strong data-upd-lic-domain>{{ $lic['domain'] ?? '—' }}</strong></li>
            <li>انقضا: <strong data-upd-lic-expires>
                @if(!empty($lic['expires_jalali']))
                    {{ $lic['expires_jalali'] }}
                @elseif(!empty($lic['expires_at']))
                    {{ $lic['expires_at'] }}
                @else
                    —
                @endif
            </strong></li>
            <li>امکان آپدیت: <strong data-upd-lic-can>{{ $canUpdate ? 'بله' : 'خیر — قفل' }}</strong></li>
        </ul>
        @if(!$canUpdate && empty($isSeller))
            <p class="upd-lic-hint">برای رفع قفل: تمدید/فعال‌سازی لایسنس یا تماس با فروشنده. سپس «بررسی همین الان» را بزنید.</p>
        @endif
    </section>

    <section class="sys-grid">
        <article class="sys-card tone-cache upd-card" data-upd-card>
            <div class="sys-card-mark">زنده</div>
            <h3>بررسی آپدیت</h3>
            <p>ابتدا لایسنس، سپس مقایسه نسخه نصب‌شده با آخرین انتشار سرور.</p>
            <button type="button" class="btn btn-secondary" data-upd-refresh>بررسی همین الان</button>
            <div class="upd-badge" data-upd-badge hidden>آپدیت موجود است</div>
            <div class="upd-badge upd-badge-lock" data-upd-lic-lock {{ $canUpdate ? 'hidden' : '' }}>آپدیت به‌خاطر لایسنس قفل است</div>
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
                <button class="btn btn-primary" type="submit" data-upd-apply-btn
                    {{ (empty($status['has_update']) || !$canUpdate) ? 'disabled' : '' }}>
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
            @if(!empty($lastResult['license']['label']))
                <p class="muted">لایسنس در زمان نصب: {{ $lastResult['license']['label'] }} — {{ $lastResult['license']['message'] ?? '' }}</p>
            @endif
            @if(!empty($lastResult['details']))
                <pre class="upd-log">{{ implode("\n", $lastResult['details']) }}</pre>
            @endif
        </section>
    @endif
</div>

<style>
.upd-license{margin:14px 0;padding:14px 16px;border:1px solid #dbe3ef;background:linear-gradient(180deg,#f8fafc,#fff);border-radius:10px}
.upd-license[data-valid="0"]{border-color:#fecaca;background:linear-gradient(180deg,#fff5f5,#fff)}
.upd-license[data-valid="1"]{border-color:#bbf7d0;background:linear-gradient(180deg,#f0fdf4,#fff)}
.upd-license-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
.upd-lic-pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;border:1px solid transparent}
.upd-lic-pill.is-ok{background:#dcfce7;color:#166534;border-color:#86efac}
.upd-lic-pill.is-bad{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
.upd-lic-msg{margin:10px 0 8px;line-height:1.7}
.upd-lic-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:6px 14px;margin:0;padding:0;list-style:none;font-size:13px}
.upd-lic-hint{margin:10px 0 0;font-size:13px;color:#9a3412}
.upd-live-row{display:flex;align-items:center;gap:8px;margin-top:12px;font-size:12px;color:#475569}
.upd-live-dot{width:10px;height:10px;border-radius:50%;background:#94a3b8;display:inline-block;box-shadow:0 0 0 0 rgba(34,197,94,.4)}
.upd-live-dot.is-on{background:#16a34a;animation:updPulse 1.6s ease-out infinite}
.upd-live-dot.is-err{background:#dc2626;animation:none}
@keyframes updPulse{0%{box-shadow:0 0 0 0 rgba(22,163,74,.45)}70%{box-shadow:0 0 0 10px rgba(22,163,74,0)}100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}}
.upd-badge{display:inline-block;margin-top:10px;margin-left:6px;padding:4px 10px;border:1px solid #f5d59a;background:#fff6e5;color:#92400e;border-radius:6px;font-size:12px;font-weight:700}
.upd-badge-lock{border-color:#fca5a5;background:#fef2f2;color:#991b1b}
.upd-confirm{display:flex;align-items:flex-start;gap:8px;margin:10px 0;font-size:13px;line-height:1.5}
.upd-changelog{margin:0;padding-right:18px;line-height:1.7}
.upd-log{background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;overflow:auto;max-height:280px;direction:ltr;text-align:left}
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
    var lockBadge = root.querySelector('[data-upd-lic-lock]');
    var applyBtn = root.querySelector('[data-upd-apply-btn]');
    var licBox = root.querySelector('[data-upd-license]');
    var busy = false;

    function setText(sel, val) {
        var el = root.querySelector(sel);
        if (el) el.textContent = val == null || val === '' ? '—' : String(val);
    }

    function paintLicense(lic) {
        if (!lic || !licBox) return;
        var valid = !!lic.valid;
        var can = !!lic.can_update;
        licBox.setAttribute('data-valid', valid ? '1' : '0');
        licBox.setAttribute('data-can-update', can ? '1' : '0');
        setText('[data-upd-lic-label]', lic.label || '—');
        setText('[data-upd-lic-msg]', lic.message || '');
        setText('[data-upd-lic-code]', lic.code || '—');
        setText('[data-upd-lic-plan]', lic.plan || '—');
        setText('[data-upd-lic-domain]', lic.domain || '—');
        setText('[data-upd-lic-expires]', lic.expires_jalali || lic.expires_at || '—');
        setText('[data-upd-lic-can]', can ? 'بله' : 'خیر — قفل');
        var pill = root.querySelector('[data-upd-lic-pill]');
        if (pill) {
            pill.textContent = valid ? 'معتبر' : 'نامعتبر';
            pill.classList.toggle('is-ok', valid);
            pill.classList.toggle('is-bad', !valid);
        }
        if (lockBadge) lockBadge.hidden = can;
        return can;
    }

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
        var can = paintLicense(data.license || {});
        var has = !!data.has_update;
        if (badge) badge.hidden = !has || !can;
        if (applyBtn) applyBtn.disabled = !(has && can);
        if (dot) {
            dot.classList.toggle('is-on', !!data.ok && can);
            dot.classList.toggle('is-err', !data.ok || !can);
        }
        if (label) {
            if (!can) {
                label.textContent = 'لایسنس نامعتبر — آپدیت قفل است';
            } else if (data.ok) {
                label.textContent = has
                    ? ('آپدیت ' + (data.latest || '') + ' آماده است — بررسی زنده فعال')
                    : ('به‌روز هستید — بررسی زنده هر ' + (poll/1000) + ' ثانیه');
            }
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
