@extends('auth-customers::account.layout')
@section('title','حساب کاربری')
@section('account')
<style>
  .dash-hero{position:relative;overflow:hidden;margin-bottom:15px;padding:25px 27px;border-radius:20px;color:#fff;background:linear-gradient(135deg,#172554,#1d4ed8 62%,#38bdf8);box-shadow:0 14px 35px rgba(29,78,216,.16)}.dash-hero:after{content:"";position:absolute;width:180px;height:180px;left:-55px;top:-90px;border-radius:50%;background:rgba(255,255,255,.1)}.dash-hero h1{position:relative;margin:0;color:#fff;font-size:23px}.dash-hero p{position:relative;margin:6px 0 0;color:rgba(255,255,255,.78);font-size:12px}
  .dash-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:15px}.dash-stat{padding:17px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.045)}.dash-stat i{display:grid;place-items:center;width:34px;height:34px;margin-bottom:10px;border-radius:11px;color:#1d4ed8;background:#dbeafe;font-style:normal}.dash-stat span{display:block;color:#64748b;font-size:10.5px}.dash-stat strong{display:block;margin-top:5px;color:#0f172a;font-size:19px}
  .dash-actions{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;padding:18px}.dash-action{display:flex;align-items:center;gap:9px;min-height:55px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:13px;color:#334155;background:#f8fafc;font-size:11.5px;font-weight:850;text-decoration:none}.dash-action:hover{border-color:#bfdbfe;color:#1d4ed8;background:#eff6ff}.dash-action i{display:grid;place-items:center;width:31px;height:31px;border-radius:10px;color:#fff;background:#2563eb;font-style:normal}
  .dash-title{margin:0 0 13px;font-size:15px}.dash-empty{padding:25px;text-align:center;color:#64748b;font-size:12px}
  @media(max-width:720px){.dash-stats{grid-template-columns:repeat(2,1fr)}.dash-actions{grid-template-columns:1fr 1fr;padding:13px}.dash-hero{padding:21px}.dash-action{font-size:10.5px}}
</style>
<section class="dash-hero"><h1>سلام {{ $user->name }}</h1><p>همه سفارش‌ها، پرداخت‌ها و خدمات حساب شما در یک نمای ساده و سریع.</p></section>
<section class="dash-stats">
  <div class="dash-stat"><i>▣</i><span>سفارش‌ها</span><strong>{{ $ordersCount }}</strong></div>
  <div class="dash-stat"><i>◈</i><span>موجودی کیف پول</span><strong style="font-size:15px">{{ number_format((int)($walletBalance ?? 0)) }} تومان</strong></div>
  <div class="dash-stat"><i>◷</i><span>پیش‌خریدها</span><strong>{{ $preordersCount }}</strong></div>
  <div class="dash-stat"><i>⛨</i><span>امنیت حساب</span><strong style="font-size:14px">{{ $user->twoFactorLabel() }}</strong></div>
</section>
<section class="acc-card">
  <h2 class="dash-title">دسترسی سریع</h2>
  <div class="dash-actions">
    <a class="dash-action" href="{{ route('account.shop') }}"><i>◎</i> خرید محصول</a>
    <a class="dash-action" href="{{ route('account.orders') }}"><i>▣</i> سفارش‌های من</a>
    <a class="dash-action" href="{{ route('account.invoices') }}"><i>▤</i> فاکتورها</a>
    <a class="dash-action" href="{{ route('account.wallet') }}"><i>◈</i> کیف پول</a>
    <a class="dash-action" href="{{ route('account.serials') }}"><i>☰</i> سریال‌های من</a>
    <a class="dash-action" href="{{ url('/serial-check') }}"><i>⌕</i> استعلام گارانتی</a>
    <a class="dash-action" href="{{ url('/account/tickets') }}"><i>✉</i> پشتیبانی</a>
    <a class="dash-action" href="{{ route('account.profile') }}"><i>☺</i> ویرایش مشخصات</a>
    <a class="dash-action" href="{{ route('account.security') }}"><i>⛨</i> امنیت حساب</a>
  </div>
</section>
<section class="acc-card">
  <h2 class="dash-title">آخرین سفارش‌ها</h2>
  @if($recentOrders->count())
    <table class="acc-table"><thead><tr><th>کد</th><th>وضعیت</th><th>مبلغ</th><th></th></tr></thead><tbody>
    @foreach($recentOrders as $o)<tr><td>{{ $o->order_number }}</td><td><span class="acc-badge">{{ method_exists($o,'statusLabel') ? $o->statusLabel() : $o->status }}</span></td><td>{{ number_format((float)$o->total) }} تومان</td><td><a href="{{ route('account.orders.show',$o) }}">جزئیات</a></td></tr>@endforeach
    </tbody></table>
  @else
    <div class="dash-empty">هنوز سفارشی ثبت نشده است. <a href="{{ route('account.shop') }}">مشاهده محصولات</a></div>
  @endif
</section>
@endsection
