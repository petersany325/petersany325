@extends('layouts.app')
@section('title', 'همگام‌سازی رایانه | سرزمین هارد')
@section('page_title', 'همگام‌سازی نرم‌افزار رایانه')
@section('window_title', 'اتصال نرم‌افزار کامپیوتر')
@push('head')
<style>
.device-menu { position: relative; display: inline-block; }
.device-menu-pop {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    z-index: 40;
    min-width: 168px;
    padding: 6px;
    background: #fff;
    border: 1px solid #c5cdd6;
    box-shadow: 0 8px 20px rgba(20, 28, 38, .14);
}
.device-menu.is-open .device-menu-pop { display: flex; flex-direction: column; gap: 6px; }
.device-menu-pop .btn,
.device-menu-pop form .btn { width: 100%; }
.device-token {
    direction: ltr;
    unicode-bidi: plaintext;
    font-family: ui-monospace, monospace;
    font-size: 12px;
    line-height: 1.5;
    word-break: break-all;
    user-select: all;
    margin-bottom: 6px;
}
.device-token-missing { font-size: 12px; color: #66707c; margin-bottom: 6px; }
</style>
@endpush
@section('content')
<div class="panel">
    <h2>همگام‌سازی با نرم‌افزار حسابداری</h2>
    <p class="lead">یک توکن برای هر رایانه بسازید و در نرم‌افزار حسابداری وارد کنید. از آن به بعد مشتری، قبض و پرداخت از هر دو طرف به هم می‌رسند.</p>

    @if(!$ready)
        <div class="alert alert-error">جدول همگام‌سازی هنوز ساخته نشده. به‌روزرسانی نرم‌افزار را یک بار نصب کنید.</div>
    @endif

    <form method="POST" action="{{ route('settings.desktop-sync.store') }}" class="accept-row accept-row-4">
        @csrf
        <div>
            <label>نام رایانه</label>
            <input type="text" name="name" required maxlength="80" placeholder="مثلاً پذیرش ۱" value="{{ old('name') }}">
        </div>
        <div style="align-self:end;">
            <button class="btn btn-primary" type="submit" @disabled(!$ready)>ساخت توکن</button>
        </div>
    </form>
</div>

<div class="panel" style="margin-top:12px;">
    <h3>رایانه‌های وصل‌شده</h3>
    <table class="compact-table">
        <thead>
            <tr>
                <th>نام</th>
                <th>پیشوند</th>
                <th>دسترسی</th>
                <th>آخرین اتصال</th>
                <th>آخرین IP</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
                <tr>
                    <td>{{ $device->name }}</td>
                    <td dir="ltr">{{ $device->prefix ?: '—' }}</td>
                    <td>{{ implode(' و ', array_map(fn ($scope) => $scope === 'write' ? 'نوشتن' : 'خواندن', $device->tokenScopes())) }}</td>
                    <td>{{ $device->last_seen_at ? jalali_like($device->last_seen_at) : 'هنوز وصل نشده' }}</td>
                    <td dir="ltr">{{ $device->last_ip ?: '—' }}</td>
                    <td>{{ $device->revoked_at ? 'قطع‌شده' : 'فعال' }}</td>
                    <td>
                        @php $token = $device->plainToken(); @endphp
                        @if($token)
                            <div class="device-token" dir="ltr">{{ $token }}</div>
                        @elseif(!$device->revoked_at)
                            <div class="device-token-missing">توکن قبلی ذخیره نشده و دوباره نشان داده نمی‌شود.</div>
                        @endif
                        <div class="device-menu" data-device-menu>
                            <button type="button" class="btn btn-secondary" data-device-menu-btn>منو</button>
                            <div class="device-menu-pop">
                                @if($token)
                                    <button type="button" class="btn btn-primary" data-copy-token="{{ $token }}" @if((int) $copyId === (int) $device->id) data-autocopy @endif>کپی توکن</button>
                                @elseif(!$device->revoked_at)
                                    <form method="POST" action="{{ route('settings.desktop-sync.reissue', $device) }}">
                                        @csrf
                                        <button class="btn btn-primary" type="submit" data-confirm="توکن جدید ساخته می‌شود و توکن قبلی این رایانه دیگر وصل نمی‌شود. ادامه می‌دهید؟">توکن جدید</button>
                                    </form>
                                @endif
                                @if(!$device->revoked_at)
                                    <form method="POST" action="{{ route('settings.desktop-sync.revoke', $device) }}">
                                        @csrf
                                        <button class="btn btn-danger" type="submit" data-confirm="اتصال این رایانه قطع شود؟">قطع اتصال</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">هنوز رایانه‌ای وصل نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel" style="margin-top:12px;">
    <h3>قرارداد نرم‌افزار</h3>
    <p>نرم‌افزار حسابداری این آدرس را صدا می‌زند. درخواست‌ها هدر <span dir="ltr">Authorization: Bearer</span> و توکن را لازم دارند.</p>
    <ul>
        <li dir="ltr">GET {{ $apiBase }}/ping</li>
        <li dir="ltr">GET {{ $apiBase }}/changes</li>
        <li dir="ltr">POST {{ $apiBase }}/push</li>
        <li dir="ltr">GET {{ $pingUrl }}</li>
        <li dir="ltr">GET {{ $specUrl }}</li>
        <li dir="ltr">POST {{ $syncUrl }}</li>
    </ul>
    <p>بار اول <span dir="ltr">cursor</span> را خالی بفرستید. تا وقتی <span dir="ltr">has_more</span> درست است، همان <span dir="ltr">snapshot</span> را برگردانید. بعد فقط شماره <span dir="ltr">cursor</span> را نگه دارید و تغییرهای محلی را در <span dir="ltr">changes</span> بفرستید.</p>
    <pre dir="ltr" style="white-space:pre-wrap;">{
  "cursor": null,
  "changes": [
    {
      "resource": "customers",
      "sync_key": "6f1c0b2e-3a4d-4e5f-8a9b-0c1d2e3f4a5b",
      "op": "upsert",
      "base_updated_at": null,
      "data": {"name": "علی رضایی", "phone": "09120000000"}
    }
  ]
}</pre>
</div>

<div class="panel" style="margin-top:12px;">
    <h3>آخرین رویدادها</h3>
    <table class="compact-table">
        <thead>
            <tr>
                <th>زمان</th>
                <th>مسیر</th>
                <th>جدول</th>
                <th>عمل</th>
                <th>نتیجه</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at ? jalali_like($log->created_at) : '—' }}</td>
                    <td dir="ltr">{{ $log->endpoint }}</td>
                    <td dir="ltr">{{ $log->table_name ?: '—' }}</td>
                    <td dir="ltr">{{ $log->op ?: '—' }}</td>
                    <td dir="ltr">{{ $log->status ?: '—' }}</td>
                    <td dir="ltr">{{ $log->ip ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">هنوز رویدادی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
(function () {
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        var input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.left = '-9999px';
        document.body.appendChild(input);
        input.select();
        try { document.execCommand('copy'); } catch (e) {}
        input.remove();
        return Promise.resolve();
    }

    document.addEventListener('click', function (event) {
        var menuBtn = event.target.closest('[data-device-menu-btn]');
        document.querySelectorAll('[data-device-menu].is-open').forEach(function (menu) {
            if (!menuBtn || !menu.contains(menuBtn)) menu.classList.remove('is-open');
        });
        if (menuBtn) {
            menuBtn.closest('[data-device-menu]').classList.toggle('is-open');
            event.preventDefault();
        }

        var copyBtn = event.target.closest('[data-copy-token]');
        if (!copyBtn) return;
        event.preventDefault();
        copyText(copyBtn.getAttribute('data-copy-token') || '').then(function () {
            copyBtn.textContent = 'کپی شد';
        });
    });

    var auto = document.querySelector('[data-autocopy]');
    if (auto) {
        copyText(auto.getAttribute('data-copy-token') || '').then(function () {
            auto.textContent = 'کپی شد';
            var menu = auto.closest('[data-device-menu]');
            if (menu) menu.classList.add('is-open');
        });
    }
})();
</script>
@endsection
