@php $intern = $intern ?? null; @endphp
<section class="emp-section">
    <div class="emp-section-head">
        <h3>اطلاعات کارآموز</h3>
        <p>نام و موبایل برای پیامک تأیید دوره الزامی است.</p>
    </div>
    <div class="accept-row accept-row-3">
        <div>
            <label>نام کامل</label>
            <input type="text" name="name" value="{{ old('name', $intern->name ?? '') }}" required>
            @error('name')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>موبایل</label>
            <input type="text" name="phone" value="{{ old('phone', $intern->phone ?? '') }}" required placeholder="09xxxxxxxxx" dir="ltr" style="text-align:left;" inputmode="tel">
            @error('phone')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>ایمیل (اختیاری)</label>
            <input type="email" name="email" value="{{ old('email', $intern->email ?? '') }}">
        </div>
    </div>
    <div class="accept-row accept-row-3" style="margin-top:6px;">
        <div>
            <label>کد ملی (اختیاری)</label>
            <input type="text" name="national_code" value="{{ old('national_code', $intern->national_code ?? '') }}" dir="ltr" style="text-align:left;">
        </div>
        <div>
            <label>بخش / واحد</label>
            <input type="text" name="department" value="{{ old('department', $intern->department ?? '') }}" placeholder="مثلاً پذیرش / کارگاه">
        </div>
        <div>
            @include('partials.toggle', [
                'name' => 'is_active',
                'label' => 'وضعیت فعال',
                'checked' => (bool) old('is_active', $intern->is_active ?? true),
            ])
        </div>
    </div>
</section>

<section class="emp-section">
    <div class="emp-section-head">
        <h3>بازه کارآموزی</h3>
        <p>همین تاریخ‌ها در پیامک تأیید برای کارآموز ارسال می‌شود.</p>
    </div>
    <div class="accept-row accept-row-2">
        <div>
            <label>از تاریخ</label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($intern->start_date ?? null)->format('Y-m-d') ?: now('Asia/Tehran')->toDateString()) }}" required>
            @error('start_date')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label>تا تاریخ</label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($intern->end_date ?? null)->format('Y-m-d') ?: now('Asia/Tehran')->addMonth()->toDateString()) }}" required>
            @error('end_date')<div class="alert alert-error" style="margin-top:4px;padding:4px 6px;">{{ $message }}</div>@enderror
        </div>
    </div>
    <div style="margin-top:6px;">
        <label>یادداشت</label>
        <textarea name="notes" rows="2" placeholder="توضیحات دوره…">{{ old('notes', $intern->notes ?? '') }}</textarea>
    </div>
</section>

@unless($intern)
    <section class="emp-section">
        @include('partials.toggle', [
            'name' => 'send_welcome_sms',
            'label' => 'ارسال پیامک تأیید کارآموزی',
            'checked' => (bool) old('send_welcome_sms', true),
            'on' => 'ارسال',
            'off' => 'بدون SMS',
        ])
        <p class="hint" style="margin:6px 0 0;">متن SMS را از منوی «متن SMS خوش‌آمد» می‌توانید تغییر دهید.</p>
    </section>
@endunless

@php
    $portalUser = $intern->user ?? null;
    $portalDefault = $portalUser
        ? ($portalUser->is_active && ($portalUser->can_login_otp || $portalUser->can_login_password))
        : true;
    $portalOn = (bool) old('portal_enabled', $portalDefault);
    $selectedPerms = old('permissions', $selected ?? \App\Support\Permissions::defaultsForRole('intern'));
    $otpOn = (bool) old('can_login_otp', $portalUser->can_login_otp ?? true);
    $passOn = (bool) old('can_login_password', $portalUser->can_login_password ?? false);
@endphp
<section class="emp-section">
    <div class="emp-section-head">
        <h3>پرتال ورود کارآموز و دسترسی‌ها</h3>
        <p>مدیر بخش‌ها را برای کارآموز فعال می‌کند — مخصوصاً دفتر روزانه. خدمات شرکت را از «تنظیمات دفتر روز» تعریف کنید.</p>
    </div>
    <div class="accept-row accept-row-3" style="align-items:end;">
        <div>
            @include('partials.toggle', [
                'name' => 'portal_enabled',
                'label' => 'فعال‌سازی پرتال ورود',
                'checked' => $portalOn,
                'on' => 'فعال',
                'off' => 'خاموش',
            ])
        </div>
        <div>
            @include('partials.toggle', [
                'name' => 'can_login_otp',
                'label' => 'ورود با موبایل / SMS',
                'checked' => $otpOn,
            ])
        </div>
        <div>
            @include('partials.toggle', [
                'name' => 'can_login_password',
                'label' => 'ورود با رمز',
                'checked' => $passOn,
            ])
        </div>
    </div>
    <div class="accept-row accept-row-2" style="margin-top:8px;">
        <div>
            <label>رمز عبور (اختیاری — برای ورود با رمز)</label>
            <input type="password" name="password" autocomplete="new-password" placeholder="حداقل ۶ کاراکتر">
        </div>
        <div>
            <label>لینک پرتال</label>
            <div class="hint" dir="ltr" style="margin-top:6px;">{{ url('/intern') }} · ورود از {{ url('/login') }}</div>
        </div>
    </div>
    <div style="margin-top:10px;">
        <strong style="font-size:12px;">دسترسی بخش‌ها</strong>
        <div class="perm-tile-grid" style="margin-top:6px;">
            @foreach(($permissions ?? \App\Support\Permissions::INTERN_MANAGEABLE) as $key => $label)
                <label class="perm-tile {{ in_array($key, $selectedPerms, true) ? 'is-on' : '' }} {{ $key === 'daily_logs' ? 'is-priority' : '' }}">
                    <input type="checkbox"
                           name="permissions[]"
                           value="{{ $key }}"
                           class="perm-tile-input"
                           @checked(in_array($key, $selectedPerms, true))>
                    <span class="perm-tile-dot"></span>
                    <span class="perm-tile-label">{{ $label }}@if($key === 'daily_logs') ★@endif</span>
                </label>
            @endforeach
        </div>
        <p class="hint" style="margin-top:6px;">خدمات قابل انجام همان دسته‌های دفتر روز هستند؛ از منوی تنظیمات دفتر روز تعریف/ویرایش کنید.</p>
    </div>
</section>
