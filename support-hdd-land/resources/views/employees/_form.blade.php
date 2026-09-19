@php
    $employee = $employee ?? null;
    $selected = old('permissions', $selected ?? []);
    $currentRole = old('role', $employee->role ?? 'receptionist');
    $roles = \App\Support\Permissions::ROLES;
    $defaultsMap = \App\Support\Permissions::defaultsMap();
    $otpDefault = old('can_login_otp', $employee->can_login_otp ?? true);
    $passDefault = old('can_login_password', $employee->can_login_password ?? false);
@endphp

<div class="emp-desk"
     id="emp-desk"
     data-defaults='@json($defaultsMap)'
     data-is-admin="{{ ($employee && $employee->isAdmin()) ? '1' : '0' }}">

    <section class="emp-section">
        <div class="emp-section-head">
            <h3>اطلاعات کارمند</h3>
            <p>نام و موبایل برای ورود با پیامک الزامی است.</p>
        </div>
        <div class="accept-row accept-row-3">
            <div>
                <label>نام کامل</label>
                <input type="text" name="name" value="{{ old('name', $employee->name ?? '') }}" required>
                @error('name')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label>موبایل (ورود SMS)</label>
                <input type="text" name="phone" value="{{ old('phone', $employee->phone ?? '') }}" required placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;" inputmode="tel">
                @error('phone')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label>ایمیل (اختیاری)</label>
                <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}">
                @error('email')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="emp-section">
        <div class="emp-section-head">
            <h3>وظیفه / کارتابل</h3>
            <p>با انتخاب وظیفه، دسترسی‌های پیشنهادی همان نقش اعمال می‌شود.</p>
        </div>
        <input type="hidden" name="role" id="emp-role" value="{{ $currentRole }}">
        <div class="duty-grid" id="duty-grid">
            @foreach($roles as $key => $meta)
                <button type="button"
                        class="duty-card tone-{{ $meta['tone'] }} {{ $currentRole === $key ? 'is-active' : '' }}"
                        data-role="{{ $key }}"
                        @disabled($employee && $employee->isAdmin() && $key !== 'admin')>
                    <span class="duty-mark">{{ $meta['mark'] }}</span>
                    <span class="duty-body">
                        <strong>{{ $meta['label'] }}</strong>
                        <small>{{ $meta['hint'] }}</small>
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="emp-section" id="tech-specialty-box" style="{{ $currentRole === 'technician' ? '' : 'display:none' }}">
        <div class="emp-section-head">
            <h3>تخصص و کمیسیون تعمیرکار</h3>
            <p>این بخش برای نقش تعمیرکار است و با منوی «تخصص و کمیسیون» یکی است.</p>
        </div>
        <div class="accept-row accept-row-2">
            <div>
                <label>تخصص (هارد، بازیابی، لپ‌تاپ…)</label>
                <input type="text" name="specialty" value="{{ old('specialty', $employee?->technician?->specialty ?? '') }}" placeholder="مثال: بازیابی اطلاعات هارد">
            </div>
            <div>
                <label>کمیسیون %</label>
                <input type="number" name="commission_percent" min="0" max="100" value="{{ old('commission_percent', $employee?->technician?->commission_percent ?? 0) }}">
            </div>
        </div>
    </section>

    <section class="emp-section">
        <div class="emp-section-head">
            <h3>نحوه ورود</h3>
            <p>کارمند هم از وب‌سرویس موبایل و هم از کامپیوتر وارد می‌شود؛ منوها خودکار با تشخیص دستگاه تنظیم می‌شوند.</p>
        </div>
        <div class="login-method-grid">
            <label class="login-method-card {{ $otpDefault ? 'is-on' : '' }}">
                <input type="checkbox" name="can_login_otp" value="1" @checked($otpDefault) class="login-method-input" data-method="otp">
                <span class="login-method-icon otp">SMS</span>
                <span class="login-method-text">
                    <strong>ورود موبایل / وب‌سرویس</strong>
                    <small>کد تأیید پیامک — مناسب گوشی و نصب روی صفحه اصلی</small>
                </span>
                <span class="login-method-state">{{ $otpDefault ? 'فعال' : 'خاموش' }}</span>
            </label>
            <label class="login-method-card {{ $passDefault ? 'is-on' : '' }}">
                <input type="checkbox" name="can_login_password" value="1" @checked($passDefault) class="login-method-input" data-method="pass">
                <span class="login-method-icon pass">رمز</span>
                <span class="login-method-text">
                    <strong>ورود کامپیوتر / رمز</strong>
                    <small>ایمیل یا موبایل + رمز — مناسب میز کار</small>
                </span>
                <span class="login-method-state">{{ $passDefault ? 'فعال' : 'خاموش' }}</span>
            </label>
            <div class="login-method-side">
                @include('partials.toggle', [
                    'name' => 'is_active',
                    'label' => 'حساب فعال',
                    'checked' => (bool) old('is_active', $employee->is_active ?? true),
                    'on' => 'فعال',
                    'off' => 'غیرفعال',
                ])
                <div>
                    <label>رمز عبور {{ $employee ? '(در صورت تغییر)' : '(اختیاری)' }}</label>
                    <input type="password" name="password" placeholder="{{ $employee ? 'خالی = بدون تغییر' : 'خالی = فقط OTP' }}">
                </div>
            </div>
        </div>
        @unless($employee)
            <div class="emp-welcome-sms" style="margin-top:8px;">
                @include('partials.toggle', [
                    'name' => 'send_welcome_sms',
                    'label' => 'ارسال پیامک خوش‌آمدگویی با لینک ورود',
                    'checked' => (bool) old('send_welcome_sms', true),
                    'on' => 'ارسال',
                    'off' => 'بدون SMS',
                ])
                <p class="hint" style="margin:6px 0 0;">متن نمونه: شما کارمند سرزمین هارد هستید + لینک ورود کارتابل</p>
            </div>
        @endunless
    </section>

    <section class="emp-section">
        <div class="emp-section-head">
            <div>
                <h3>دسترسی‌های کارتابل</h3>
                <p id="perm-hint">دسترسی‌ها مطابق وظیفه انتخاب‌شده تنظیم شده‌اند؛ قابل ویرایش دستی هستند.</p>
            </div>
            <button type="button" class="btn btn-ghost" id="reset-perms-btn">بازنشانی طبق وظیفه</button>
        </div>
        @if($employee && $employee->isAdmin())
            <div class="emp-admin-note">مدیر همیشه همه دسترسی‌ها را دارد.</div>
        @endif
        <div class="perm-tile-grid" id="perm-tile-grid">
            @foreach($permissions as $key => $label)
                <label class="perm-tile {{ in_array($key, $selected, true) ? 'is-on' : '' }} {{ ($employee && $employee->isAdmin()) ? 'is-locked' : '' }}">
                    <input type="checkbox"
                           name="permissions[]"
                           value="{{ $key }}"
                           class="perm-tile-input"
                           @checked(in_array($key, $selected, true) || ($employee && $employee->isAdmin()))
                           @disabled($employee && $employee->isAdmin())>
                    <span class="perm-tile-dot"></span>
                    <span class="perm-tile-label">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    var desk = document.getElementById('emp-desk');
    if (!desk) return;
    var defaults = {};
    try { defaults = JSON.parse(desk.getAttribute('data-defaults') || '{}'); } catch (e) {}
    var roleInput = document.getElementById('emp-role');
    var isAdmin = desk.getAttribute('data-is-admin') === '1';

    function setPerms(keys) {
        if (isAdmin) return;
        var set = {};
        (keys || []).forEach(function (k) { set[k] = true; });
        document.querySelectorAll('#perm-tile-grid .perm-tile').forEach(function (tile) {
            var cb = tile.querySelector('.perm-tile-input');
            if (!cb) return;
            var on = !!set[cb.value];
            cb.checked = on;
            tile.classList.toggle('is-on', on);
        });
    }

    function applyRole(role, force) {
        roleInput.value = role;
        document.querySelectorAll('#duty-grid .duty-card').forEach(function (card) {
            card.classList.toggle('is-active', card.getAttribute('data-role') === role);
        });
        var techBox = document.getElementById('tech-specialty-box');
        if (techBox) techBox.style.display = role === 'technician' ? '' : 'none';
        if (force || !isAdmin) {
            setPerms(defaults[role] || ['dashboard', 'profile']);
            var hint = document.getElementById('perm-hint');
            if (hint) hint.textContent = 'دسترسی‌های پیشنهادی وظیفه «' + role + '» اعمال شد.';
        }
    }

    document.querySelectorAll('#duty-grid .duty-card').forEach(function (card) {
        card.addEventListener('click', function () {
            if (card.disabled) return;
            applyRole(card.getAttribute('data-role'), true);
        });
    });

    var resetBtn = document.getElementById('reset-perms-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            applyRole(roleInput.value || 'receptionist', true);
        });
    }

    document.querySelectorAll('#perm-tile-grid .perm-tile').forEach(function (tile) {
        var cb = tile.querySelector('.perm-tile-input');
        if (!cb || cb.disabled) return;
        cb.addEventListener('change', function () {
            tile.classList.toggle('is-on', cb.checked);
        });
    });

    document.querySelectorAll('.login-method-input').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var card = cb.closest('.login-method-card');
            if (!card) return;
            card.classList.toggle('is-on', cb.checked);
            var st = card.querySelector('.login-method-state');
            if (st) st.textContent = cb.checked ? 'فعال' : 'خاموش';
        });
    });
})();
</script>
@endpush
