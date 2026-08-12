@extends('layouts.admin')
@section('title','گزارش کار و ورود/خروج کارمندان')
@section('content')
@php
  $labels = [
    'login' => 'ورود',
    'logout' => 'خروج',
    'login_failed' => 'ورود ناموفق',
    'login_denied_not_staff' => 'رد ورود (غیرکارمند)',
    'login_admin_via_staff' => 'ورود ادمین از لینک کارمند',
    'dashboard_view' => 'مشاهده داشبورد',
    'reports_view' => 'مشاهده گزارش',
    'orders_view' => 'مشاهده سفارش‌ها',
    'products_view' => 'مشاهده محصولات',
    'tickets_view' => 'مشاهده تیکت',
    'serials_view' => 'مشاهده سریال',
    'accounting_view' => 'مشاهده حسابداری',
    'staff_create' => 'ثبت کارمند',
    'staff_update' => 'ویرایش کارمند',
    'staff_delete' => 'حذف کارمند',
    'staff_settings_save' => 'ذخیره تنظیمات HR',
  ];
@endphp
<div class="vb-page">
  <div class="vb-page-head" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;align-items:center">
    <div>
      <h1>گزارش کار و ورود/خروج</h1>
      <p>تمام ورود، خروج و فعالیت‌های کارمندان در سایت</p>
    </div>
    <form method="get" class="row" style="gap:.4rem;align-items:end">
      <label>روز<input type="number" name="days" value="{{ $days }}" min="1" max="90" style="width:80px"></label>
      <label>کارمند
        <select name="staff_id">
          <option value="0">همه</option>
          @foreach($staffList as $s)
            <option value="{{ $s->id }}" @selected($staffId==$s->id)>{{ $s->name }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn btn-primary" type="submit">اعمال</button>
    </form>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.6rem;margin:1rem 0">
    @forelse($counts as $action => $c)
      <div class="panel" style="padding:.85rem">
        <span class="muted" style="font-size:.8rem">{{ $labels[$action] ?? $action }}</span>
        <strong style="display:block;font-size:1.2rem">{{ $c }}</strong>
      </div>
    @empty
      <div class="panel" style="padding:1rem" class="muted">هنوز رویدادی ثبت نشده.</div>
    @endforelse
  </div>

  <div class="panel" style="overflow:auto">
    <table class="table">
      <thead><tr><th>زمان</th><th>کارمند</th><th>رویداد</th><th>مسیر</th><th>IP</th><th>جزئیات</th></tr></thead>
      <tbody>
      @forelse($logs as $log)
        <tr>
          <td style="white-space:nowrap">{{ substr((string)$log->created_at,0,16) }}</td>
          <td>{{ $names[$log->user_id] ?? ('#'.$log->user_id) }}</td>
          <td>{{ $labels[$log->action] ?? $log->action }}</td>
          <td><code style="font-size:.75rem">{{ $log->method }} {{ $log->path }}</code></td>
          <td>{{ $log->ip }}</td>
          <td style="font-size:.8rem;max-width:220px;overflow:hidden;text-overflow:ellipsis">{{ $log->meta }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted" style="text-align:center;padding:1.2rem">لاگی نیست.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
