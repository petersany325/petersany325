@php
    $updBanner = null;
    try {
        if (auth()->check() && auth()->user()->canAccess('system.tools') && ! request()->routeIs('system-tools.updates*')) {
            $updBanner = app(\App\Services\AppUpdateService::class)->bannerStatus();
        }
    } catch (\Throwable $e) {
        $updBanner = null;
    }
@endphp
@if(!empty($updBanner['has_update']))
    <div class="upd-banner" role="status">
        <div>
            <strong>آپدیت جدید آماده است</strong>
            <span class="muted">— از {{ $updBanner['current'] ?? '?' }} به {{ $updBanner['latest'] ?? '?' }}</span>
        </div>
        <a class="btn btn-primary" href="{{ route('system-tools.updates') }}">مشاهده و نصب</a>
    </div>
@elseif(!empty($updBanner['license_locked']))
    <div class="upd-banner upd-banner-lic" role="status">
        <div>
            <strong>لایسنس برای آپدیت معتبر نیست</strong>
            <span class="muted">— {{ $updBanner['license']['label'] ?? 'نامعتبر' }}: {{ $updBanner['license']['message'] ?? ($updBanner['message'] ?? '') }}</span>
        </div>
        <a class="btn btn-secondary" href="{{ route('system-tools.updates') }}">گزارش لایسنس</a>
    </div>
@endif
@if(!empty($updBanner['has_update']) || !empty($updBanner['license_locked']))
    <style>
    .upd-banner{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 14px;margin:0 0 12px;border:1px solid #f5d59a;background:linear-gradient(90deg,#fff8eb,#fff);border-radius:8px}
    .upd-banner strong{color:#92400e}
    .upd-banner-lic{border-color:#fca5a5;background:linear-gradient(90deg,#fef2f2,#fff)}
    .upd-banner-lic strong{color:#991b1b}
    </style>
@endif
