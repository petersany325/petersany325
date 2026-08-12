@extends('layouts.admin')
@section('title','کارمندان')
@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-settings.css') }}?v=1">
@php
  $s = $s ?? [];
  $f = fn ($k, $d = null) => old($k, $s[$k] ?? $d);
  $on = fn ($k) => ! empty(old($k, $s[$k] ?? false));
  $nf = fn ($n) => number_format((int) $n);
@endphp

<div class="vb-page">
  <div class="vb-page-head" style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
    <div>
      <h1>کارمندان و دسترسی</h1>
      <p>فقط مدیر سیستم ثبت‌نام می‌کند · پنل کارمند جدا از مشتری · سود و کمیسیون</p>
    </div>
    <div class="row" style="gap:.4rem;flex-wrap:wrap">
      <a class="btn btn-primary" href="{{ url('/admin/staff/create') }}">+ افزودن کارمند</a>
      <a class="btn btn-outline" href="{{ url('/admin/staff/reports') }}">گزارش سود/کمیسیون</a>
      <a class="btn btn-outline" href="{{ url('/admin/staff/activity') }}">گزارش کار و ورود/خروج</a>
      <a class="btn btn-dark" href="{{ $loginUrl ?? url('/staff/login') }}" target="_blank">لینک ورود کارمندان</a>
    </div>
  </div>

  <div class="staff-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.7rem;margin:1rem 0">
    <div class="panel" style="padding:1rem"><span class="muted">فروش امروز</span><strong style="display:block;font-size:1.3rem">{{ $nf($today['gross'] ?? 0) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">سود امروز</span><strong style="display:block;font-size:1.3rem;color:#0f7a4b">{{ $nf($today['profit'] ?? 0) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">حاشیه امروز</span><strong style="display:block;font-size:1.3rem">{{ $today['margin'] ?? 0 }}٪</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">{{ $days }} روز · فروش</span><strong style="display:block;font-size:1.3rem">{{ $nf($range['gross'] ?? 0) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">{{ $days }} روز · کمیسیون</span><strong style="display:block;font-size:1.3rem">{{ $nf($range['commission'] ?? 0) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">ورودها ({{ $days }}ر)</span><strong style="display:block;font-size:1.3rem">{{ (int)(($activityCounts['login'] ?? 0) + ($activityCounts['login_admin_via_staff'] ?? 0)) }}</strong></div>
    <div class="panel" style="padding:1rem"><span class="muted">خروج‌ها</span><strong style="display:block;font-size:1.3rem">{{ (int)($activityCounts['logout'] ?? 0) }}</strong></div>
  </div>

  <div class="panel" style="margin-bottom:1rem;padding:1rem;background:#fff7f4;border:1px solid #f0b09d">
    <strong>لینک امن ورود کارمندان (خصوصی):</strong>
    <div class="row" style="gap:.5rem;margin-top:.5rem;flex-wrap:wrap;align-items:center">
      <code dir="ltr" style="word-break:break-all">{{ $loginUrl ?? '' }}</code>
      <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(@json($loginUrl ?? ''))">کپی</button>
    </div>
    <p class="muted" style="margin:.5rem 0 0;font-size:.85rem">آدرس عمومی /staff/login بسته است. ورود فقط با این لینک + رمز + کد SMS.</p>
  </div>

  <div class="panel" style="margin-bottom:1rem">
    <h3 style="margin-top:0">رتبه‌بندی کارمندان ({{ $days }} روز)</h3>
    <div style="overflow:auto">
      <table class="table">
        <thead><tr><th>کارمند</th><th>نقش</th><th>فروش</th><th>سود</th><th>حاشیه</th><th>کمیسیون</th><th>٪ کمیسیون</th></tr></thead>
        <tbody>
        @forelse($leaderboard as $row)
          <tr>
            <td>{{ $row->staff->name }}</td>
            <td>{{ $roles[$row->staff->role] ?? $row->staff->role }}</td>
            <td>{{ $nf($row->gross) }}</td>
            <td>{{ $nf($row->profit) }}</td>
            <td>{{ $row->margin }}٪</td>
            <td>{{ $nf($row->commission) }}</td>
            <td>{{ $row->staff->commission_rate }}٪</td>
          </tr>
        @empty
          <tr><td colspan="7" class="muted" style="text-align:center;padding:1rem">هنوز فروشی با sold_by ثبت نشده.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:.8rem">
      <h3 style="margin:0">لیست کارمندان</h3>
      <a class="btn btn-primary btn-sm" href="{{ url('/admin/staff/create') }}">افزودن</a>
    </div>
    <div style="overflow:auto">
      <table class="table">
        <thead><tr><th>نام</th><th>کد معرف</th><th>ورود</th><th>نقش</th><th>کمیسیون</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
        @forelse($staff as $m)
          <tr>
            <td>
              <strong>{{ $m->name }}</strong>
              <div class="muted" style="font-size:.8rem">{{ $m->email }} @if($m->phone)· {{ $m->phone }}@endif</div>
            </td>
            <td>
              @if(!empty($m->referral_code))
                <code dir="ltr" style="font-weight:700;letter-spacing:.04em">{{ $m->referral_code }}</code>
                <div class="muted" style="font-size:.72rem" dir="ltr">/?ref={{ $m->referral_code }}</div>
              @else
                <span class="muted">—</span>
              @endif
            </td>
            <td class="muted" style="font-size:.85rem">{{ $m->last_login_at ? \Plugins\AdminCore\src\Support\JalaliDate::format($m->last_login_at) : '—' }}</td>
            <td>{{ $roles[$m->role] ?? $m->role }}</td>
            <td>{{ $m->commission_rate }}٪</td>
            <td>{{ !empty($m->is_active) ? 'فعال' : 'غیرفعال' }}</td>
            <td class="row" style="gap:.35rem;flex-wrap:wrap">
              <a class="btn btn-outline btn-sm" href="{{ url('/admin/staff/'.$m->id.'/edit') }}">ویرایش</a>
              <form method="post" action="{{ url('/admin/staff/'.$m->id.'/toggle') }}">@csrf
                <button class="btn btn-outline btn-sm" type="submit">{{ !empty($m->is_active) ? 'غیرفعال' : 'فعال' }}</button>
              </form>
              <form method="post" action="{{ url('/admin/staff/'.$m->id.'/delete') }}" onsubmit="return confirm('حذف کارمند؟')">@csrf
                <button class="btn btn-outline btn-sm" type="submit">حذف</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="muted" style="text-align:center;padding:1.2rem">هنوز کارمندی ثبت نشده.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <form method="post" action="{{ url('/admin/staff/settings') }}" style="margin-top:1rem">@csrf
    <div class="vb-block">
      <div class="vb-block-head"><span>تنظیمات ماژول و امنیت ورود</span></div>
      <div class="vb-block-body">
        <div class="vb-opt">
          <div><span class="vb-title">فعال بودن ماژول</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">SMS اجباری هنگام ورود کارمند</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="force_sms_login" value="1" @checked($on('force_sms_login'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">تولید مجدد لینک امن ورود</span><span class="vb-desc">لینک قبلی باطل می‌شود</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="regenerate_login_secret" value="1"><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">ردیابی کمیسیون</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="track_commission" value="1" @checked($on('track_commission'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">نمایش حقوق پایه</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="show_salary" value="1" @checked($on('show_salary'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">سیستم کد معرف کارمند</span><span class="vb-desc">مشتری کد یا لینک ?ref= را وارد می‌کند و کارمند در سود شریک می‌شود</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="referral_enabled" value="1" @checked($on('referral_enabled'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">نمایش فیلد کد در چک‌اوت</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="referral_show_checkout" value="1" @checked($on('referral_show_checkout'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">واریز خودکار به کیف پول پس از پرداخت</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="referral_credit_on_paid" value="1" @checked($on('referral_credit_on_paid'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">مسدود کردن معرفی خود (self-referral)</span></div>
          <div class="vb-ctrl"><label class="vb-switch"><input type="checkbox" name="referral_block_self" value="1" @checked($on('referral_block_self'))><span class="vb-slider"></span></label></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">مدت کوکی معرف (روز)</span><span class="vb-desc">پس از کلیک روی لینک ?ref=</span></div>
          <div class="vb-ctrl"><input type="number" min="1" max="90" name="referral_cookie_days" value="{{ $f('referral_cookie_days',14) }}"></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">حداقل مبلغ سفارش برای کمیسیون (تومان)</span></div>
          <div class="vb-ctrl"><input type="number" min="0" name="referral_min_subtotal" value="{{ $f('referral_min_subtotal',0) }}"></div>
        </div>
        <div class="vb-opt vb-opt-stack">
          <div><span class="vb-title">راهنمای مشتری در چک‌اوت</span></div>
          <div class="vb-ctrl"><input type="text" name="referral_customer_hint" value="{{ $f('referral_customer_hint','اگر کارمند فروشگاه شما را معرفی کرده، کد معرف را وارد کنید.') }}"></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">نقش پیش‌فرض منو</span></div>
          <div class="vb-ctrl"><input type="text" name="default_role" value="{{ $f('default_role','seller') }}"></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">نرخ کمیسیون پیش‌فرض (٪)</span></div>
          <div class="vb-ctrl"><input type="number" step="0.1" name="default_commission_rate" value="{{ $f('default_commission_rate',2) }}"></div>
        </div>
        <div class="vb-opt vb-opt-stack">
          <div><span class="vb-title">نقش‌های منو (اختیاری)</span><span class="vb-desc">هر خط: کلید|عنوان — فقط برای پر کردن سریع سوئیچ‌ها</span></div>
          <div class="vb-ctrl"><textarea name="roles" rows="6">{{ $f('roles') }}</textarea></div>
        </div>
        <div class="vb-opt">
          <div><span class="vb-title">دپارتمان‌ها</span></div>
          <div class="vb-ctrl"><input type="text" name="departments" value="{{ $f('departments') }}"></div>
        </div>
        <input type="hidden" name="require_phone" value="1">
        <input type="hidden" name="login_secret" value="{{ $f('login_secret') }}">
        <p class="muted" style="font-size:.85rem">ثبت‌نام فقط توسط ادمین · ورود کارمند فقط با لینک امن + SMS</p>
        <div class="vb-actions"><button class="btn btn-primary btn-sm" type="submit">ذخیره تنظیمات</button></div>
      </div>
    </div>
  </form>
</div>
@endsection
