@extends('layouts.admin')
@section('title', ($member ? 'ویرایش کارمند' : 'افزودن کارمند'))
@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}?v=1">
@php
  $m = $member;
  $perms = $m ? \Plugins\StaffHR\src\Support\StaffAcl::normalizePermissions($m->permissions ?? []) : [];
  $presetJson = json_encode(collect($rolePresets)->map(fn($v) => $v['permissions']), JSON_UNESCAPED_UNICODE);
  $depts = array_filter(array_map('trim', explode(',', (string) ($s['departments'] ?? ''))));
  $curRole = old('role', $m->role ?? 'custom');
@endphp

<div class="vb-page">
  <div class="vb-page-head">
    <h1>{{ $m ? 'ویرایش کارمند' : 'افزودن کارمند' }}</h1>
    <p>موبایل برای SMS ورود اجباری است · دسترسی‌ها با سوئیچ روشن/خاموش · لینک امن: <code dir="ltr">{{ $loginUrl ?? '' }}</code></p>
  </div>

  <form method="post" action="{{ $m ? url('/admin/staff/'.$m->id) : url('/admin/staff') }}" class="panel" style="padding:1.2rem">
    @csrf
    @if($m) @method('PUT') @endif

    <div class="form-2">
      <label>نام کامل<input name="name" value="{{ old('name', $m->name ?? '') }}" required></label>
      <label>ایمیل<input type="email" name="email" value="{{ old('email', $m->email ?? '') }}" {{ $m ? '' : 'required' }}></label>
      <label>موبایل (الزامی برای SMS)
        <input name="phone" value="{{ old('phone', $m->phone ?? '') }}" placeholder="09xxxxxxxxx" required>
      </label>
      <label>رمز عبور {{ $m ? '(خالی = بدون تغییر)' : '' }}
        <input type="password" name="password" {{ $m ? '' : 'required' }} minlength="6" autocomplete="new-password">
      </label>
      <label>نقش (اختیاری — فقط برای پر کردن سریع سوئیچ‌ها)
        <select name="role" id="staffRole">
          <option value="custom" @selected($curRole==='custom' || $curRole==='')>— بدون نقش · فقط سوئیچ‌ها —</option>
          @foreach($roles as $k => $lab)
            @if($k !== 'custom')
              <option value="{{ $k }}" @selected($curRole===$k)>{{ $lab }}</option>
            @endif
          @endforeach
        </select>
      </label>
      <label>دپارتمان
        <select name="department">
          <option value="">—</option>
          @foreach($depts as $d)
            <option value="{{ $d }}" @selected(old('department', $m->department ?? '')===$d)>{{ $d }}</option>
          @endforeach
        </select>
      </label>
      <label>درصد کمیسیون از سود فروش (٪)
        <input type="number" step="0.1" min="0" max="50" name="commission_rate"
               value="{{ old('commission_rate', $m->commission_rate ?? ($s['default_commission_rate'] ?? 2)) }}">
      </label>
      <label>تاریخ استخدام<input type="date" name="hired_at" value="{{ old('hired_at', $m->hired_at ?? now()->toDateString()) }}"></label>
    </div>

    @if($m && !empty($m->referral_code))
      <div class="panel" style="margin-top:1rem;padding:1rem;background:#f0fdf6;border:1px solid #86efac">
        <h3 style="margin:0 0 .5rem;font-size:1rem">کد معرف فروش</h3>
        <p class="muted" style="margin:0 0 .6rem;font-size:.85rem">مشتری این کد را در چک‌اوت وارد می‌کند یا لینک را باز می‌کند؛ پس از پرداخت، کمیسیون به کیف پول کارمند واریز می‌شود.</p>
        <div class="row" style="gap:.5rem;flex-wrap:wrap;align-items:center">
          <code dir="ltr" style="font-size:1.15rem;letter-spacing:.08em;font-weight:700">{{ $m->referral_code }}</code>
          <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(@json($m->referral_code))">کپی کد</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(@json(url('/?ref='.$m->referral_code)))">کپی لینک</button>
        </div>
        <p class="muted" style="margin:.5rem 0 0;font-size:.8rem" dir="ltr">{{ url('/?ref='.$m->referral_code) }}</p>
        <form method="post" action="{{ url('/admin/staff/'.$m->id.'/regenerate-code') }}" style="margin-top:.75rem" onsubmit="return confirm('کد قبلی باطل می‌شود. ادامه؟')">
          @csrf
          <button class="btn btn-outline btn-sm" type="submit">تولید مجدد کد</button>
        </form>
      </div>
    @endif

    <div style="margin-top:1.2rem;padding:1rem;border:1px solid var(--line);border-radius:14px;background:#fafbfc">
      <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:.5rem">
        <h3 style="margin:0;font-size:1rem">دسترسی‌ها (روشن / خاموش)</h3>
        <button type="button" class="btn btn-outline btn-sm" id="btnApplyRole">اعمال پیش‌فرض نقش انتخاب‌شده</button>
      </div>
      <div class="vb-block-body" style="padding:0" id="permBox">
        @foreach($permissionLabels as $key => $label)
          <div class="vb-opt">
            <div>
              <span class="vb-title">{{ $label }}</span>
              <span class="vb-desc" style="display:block;font-size:.78rem;color:#6b7280">{{ $key }}</span>
            </div>
            <div class="vb-ctrl">
              <label class="vb-switch">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="perm-cb" @checked(in_array($key, old('permissions', $perms), true))>
                <span class="vb-slider"></span>
              </label>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="vb-block" style="margin-top:1rem">
      <div class="vb-block-body" style="padding:0">
        <div class="vb-opt">
          <div><span class="vb-title">حساب فعال</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $m->is_active ?? true))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">دیدن سود فروشگاه در پنل خودش</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="can_see_profit" value="1" @checked(old('can_see_profit', $m->can_see_profit ?? false))><span class="vb-slider"></span></label></div>
        </div>
      </div>
    </div>

    <label style="margin-top:1rem">یادداشت<textarea name="notes" rows="3">{{ old('notes', $m->notes ?? '') }}</textarea></label>

    <div class="row" style="gap:.5rem;margin-top:1.2rem">
      <button class="btn btn-primary" type="submit">ذخیره کارمند</button>
      <a class="btn btn-outline" href="{{ url('/admin/staff') }}">بازگشت</a>
    </div>
  </form>

  @if($m)
    <div class="row" style="gap:.5rem;margin-top:.8rem">
      <form method="post" action="{{ url('/admin/staff/'.$m->id.'/toggle') }}" onsubmit="return confirm('تغییر وضعیت فعال؟')">@csrf
        <button class="btn btn-outline" type="submit">{{ !empty($m->is_active) ? 'غیرفعال کردن' : 'فعال کردن' }}</button>
      </form>
      <form method="post" action="{{ url('/admin/staff/'.$m->id.'/delete') }}" onsubmit="return confirm('حذف قطعی کارمند؟')">@csrf
        <button class="btn btn-outline" type="submit" style="color:#a32012">حذف</button>
      </form>
    </div>
  @endif
</div>

<script>
(function(){
  var presets = {!! $presetJson !!};
  function applyRole(){
    var role = document.getElementById('staffRole');
    var list = presets[role.value] || [];
    document.querySelectorAll('.perm-cb').forEach(function(cb){
      cb.checked = list.indexOf(cb.value) !== -1;
    });
  }
  document.getElementById('btnApplyRole')?.addEventListener('click', applyRole);
})();
</script>
@endsection
