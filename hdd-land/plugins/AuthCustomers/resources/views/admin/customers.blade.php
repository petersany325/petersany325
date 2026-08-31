@extends('layouts.admin')
@section('title','مشتریان')
@section('content')
@php $balances = $balances ?? []; @endphp
<style>
.cu-ops{display:flex;gap:.35rem;flex-wrap:wrap;align-items:center}
.cu-ops .btn{white-space:nowrap}
.cu-ops form{margin:0}
.btn-danger-soft{
  border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;border-radius:999px;
  padding:.38rem .7rem;font:inherit;font-weight:800;font-size:.78rem;cursor:pointer
}
.btn-danger-soft:hover{background:#ffe4e6}
</style>
<div class="panel">
  <div style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <div>
      <h1 style="margin:0;font-size:1.25rem">لیست مشتریان</h1>
      <p style="margin:.35rem 0 0;color:#6b7280;font-size:.88rem">مدیریت حساب‌ها، کیف پول، ۲FA و شبا</p>
    </div>
    <div style="display:flex;gap:.45rem;flex-wrap:wrap">
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/wallet-settings') }}">تنظیمات کیف پول</a>
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/auth-settings?tab=register') }}">تنظیمات ثبت‌نام</a>
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/auth-settings?tab=refund') }}">عودت وجه / شبا</a>
      <a class="btn btn-primary btn-sm" href="{{ url('/register') }}" target="_blank" rel="noopener">صفحه ثبت‌نام</a>
    </div>
  </div>

  <form method="get" style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
    <input type="search" name="q" value="{{ $search }}" placeholder="جستجو نام / ایمیل / موبایل" style="flex:1;min-width:200px;padding:.55rem .7rem;border:1px solid #e5e7eb;border-radius:10px">
    <button class="btn btn-outline" type="submit">جستجو</button>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>نام</th>
        <th>موبایل</th>
        <th>کیف پول</th>
        <th>بانک / شبا</th>
        <th>۲FA</th>
        <th>تعدیل کیف پول</th>
        <th>عملیات</th>
      </tr>
    </thead>
    <tbody>
    @forelse($customers as $c)
      <tr>
        <td>
          <strong>{{ $c->name }}</strong>
          <div style="font-size:.78rem;color:#6b7280">{{ $c->email }}</div>
        </td>
        <td>
          {{ $c->phone ?: '—' }}
          @if($c->phone_verified_at)
            <span style="color:#15803d;font-size:.75rem;font-weight:700">✓</span>
          @endif
        </td>
        <td><strong>{{ number_format((int)($balances[$c->id] ?? 0)) }}</strong> <small>تومان</small></td>
        <td style="font-size:.82rem;line-height:1.5">
          @if($c->bank_name || $c->bank_iban || $c->bank_card)
            <div>{{ $c->bank_name ?: '—' }}</div>
            @if($c->bank_iban)<code>{{ $c->bank_iban }}</code>@endif
          @else
            <span style="color:#9ca3af">—</span>
          @endif
        </td>
        <td>
          @if($c->two_factor_enabled)
            <span style="background:#edfaf3;color:#15803d;padding:.1rem .45rem;border-radius:999px;font-size:.75rem;font-weight:800">{{ $c->twoFactorLabel() }}</span>
          @else
            <span style="color:#9ca3af;font-size:.8rem">خاموش</span>
          @endif
        </td>
        <td>
          <form method="post" action="{{ url('/admin/customers/'.$c->id.'/wallet') }}" style="display:flex;gap:.3rem;flex-wrap:wrap;align-items:center">@csrf
            <input type="number" name="amount" min="1" placeholder="مبلغ" required style="width:6.5rem;padding:.35rem .45rem;border:1px solid #e5e7eb;border-radius:8px">
            <select name="direction" style="padding:.35rem;border:1px solid #e5e7eb;border-radius:8px">
              <option value="credit">افزایش</option>
              <option value="debit">کاهش</option>
            </select>
            <button class="btn btn-outline btn-sm" type="submit">اعمال</button>
          </form>
        </td>
        <td>
          <div class="cu-ops">
            <a class="btn btn-outline btn-sm" href="{{ url('/admin/customers/'.$c->id.'/edit') }}">ویرایش</a>
            <form method="post" action="{{ url('/admin/customers/'.$c->id.'/toggle-2fa') }}">@csrf
              <button class="btn btn-outline btn-sm" type="submit">
                {{ $c->two_factor_enabled ? 'خاموش ۲FA' : 'فعال ۲FA' }}
              </button>
            </form>
            <form method="post" action="{{ url('/admin/customers/'.$c->id) }}" onsubmit="return confirm('مشتری «{{ addslashes($c->name) }}» حذف شود؟ این عمل قابل بازگشت نیست.');">
              @csrf
              @method('DELETE')
              <button class="btn-danger-soft" type="submit">حذف</button>
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="7">مشتری‌ای ثبت نشده.</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="margin-top:1rem">{{ $customers->links() }}</div>
</div>
@endsection
