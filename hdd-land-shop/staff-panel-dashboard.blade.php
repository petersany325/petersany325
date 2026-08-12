@extends('layouts.staff')
@section('title','داشبورد کارمند')
@section('content')
@php $nf = fn ($n) => number_format((int) $n); @endphp
<h1 style="margin:0 0 .35rem">سلام {{ auth()->user()->name }}</h1>
<p class="muted" style="margin:0 0 1rem">
  {{ $staff->role ?? 'کارمند' }}
  @if($staff)
    · سهم شما: <b>{{ $staff->commission_rate }}٪</b> از <b>سود فروش</b> سفارش‌های معرف‌شده
  @endif
</p>

@if($staff && !empty($staff->referral_code))
  <div class="panel" style="padding:1.1rem;margin-bottom:1rem;background:linear-gradient(135deg,#ecfdf5,#f0f9ff);border:1px solid #a7f3d0">
    <div class="row" style="justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start">
      <div>
        <h3 style="margin:0 0 .35rem;font-size:1rem">کد معرف شما</h3>
        <p class="muted" style="margin:0;font-size:.85rem">پس از پرداخت مشتری، فروش و سود محاسبه می‌شود و {{ $staff->commission_rate }}٪ از سود به کیف پول شما واریز می‌گردد.</p>
      </div>
      <div style="text-align:left" dir="ltr">
        <div style="font-size:1.6rem;font-weight:800;letter-spacing:.1em;color:#047857">{{ $staff->referral_code }}</div>
        <div class="row" style="gap:.35rem;margin-top:.5rem;justify-content:flex-end">
          <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(@json($staff->referral_code))">کپی کد</button>
          @if(!empty($shareUrl))
            <button type="button" class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(@json($shareUrl))">کپی لینک</button>
          @endif
        </div>
      </div>
    </div>
    @if(!empty($shareUrl))
      <p class="muted" style="margin:.75rem 0 0;font-size:.8rem;word-break:break-all" dir="ltr">{{ $shareUrl }}</p>
    @endif
  </div>
@endif

<div class="panel" style="padding:1.1rem;margin-bottom:1rem;border:1px solid #bfdbfe;background:#f8fbff">
  <h3 style="margin:0 0 .75rem;font-size:1rem">کارتابل فروش شما</h3>
  <div class="staff-grid" style="margin:0">
    <div class="staff-stat">
      <span class="muted">فروش امروز</span>
      <strong>{{ $nf($today['gross']) }}</strong>
      <div class="muted" style="font-size:.75rem;margin-top:.2rem">{{ $today['orders'] }} سفارش</div>
    </div>
    <div class="staff-stat" style="border-color:#86efac">
      <span class="muted">سود فروش امروز</span>
      <strong style="color:#047857">{{ $nf($today['profit']) }}</strong>
      <div class="muted" style="font-size:.75rem;margin-top:.2rem">حاشیه {{ $today['margin'] }}٪</div>
    </div>
    <div class="staff-stat">
      <span class="muted">درصد شما</span>
      <strong>{{ $staff->commission_rate ?? 0 }}٪</strong>
      <div class="muted" style="font-size:.75rem;margin-top:.2rem">از سود فروش</div>
    </div>
    <div class="staff-stat" style="background:#f0fdf4;border-color:#86efac">
      <span class="muted">سهم امروز (واریز)</span>
      <strong style="color:#047857">{{ $nf($today['commission']) }}</strong>
    </div>
    <div class="staff-stat" style="background:#ecfdf5;border-color:#34d399">
      <span class="muted">موجودی حساب (کیف پول)</span>
      <strong style="color:#047857">{{ $nf($walletBalance ?? 0) }}</strong>
      <div class="muted" style="font-size:.75rem;margin-top:.2rem">تومان</div>
    </div>
    <div class="staff-stat">
      <span class="muted">فروش ماه</span>
      <strong>{{ $nf($month['gross']) }}</strong>
    </div>
    <div class="staff-stat">
      <span class="muted">سود ماه</span>
      <strong style="color:#047857">{{ $nf($month['profit']) }}</strong>
    </div>
    <div class="staff-stat">
      <span class="muted">کمیسیون ماه</span>
      <strong>{{ $nf($month['commission']) }}</strong>
    </div>
  </div>
</div>

<div class="panel" style="margin-top:1rem">
  <h3 style="margin-top:0">سودهای واریزشده به حساب شما</h3>
  <p class="muted" style="margin:0 0 .75rem;font-size:.85rem">فروش − قیمت خرید = سود · سپس درصد شما از سود به کیف پول واریز شده است.</p>
  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>تاریخ</th>
          <th>سفارش</th>
          <th>فروش</th>
          <th>سود فروش</th>
          <th>درصد شما</th>
          <th>واریز به حساب</th>
          <th>وضعیت</th>
        </tr>
      </thead>
      <tbody>
      @forelse(($commissions ?? collect()) as $c)
        <tr>
          <td>{{ substr((string) $c->created_at, 0, 16) }}</td>
          <td>#{{ $c->order_id }}</td>
          <td>{{ $nf($c->order_subtotal) }}</td>
          <td style="color:#047857;font-weight:600">{{ $nf($c->order_profit ?? max(0, (int)$c->order_subtotal - (int)($c->order_cost ?? 0))) }}</td>
          <td>{{ $c->rate }}٪</td>
          <td style="color:#047857;font-weight:800">{{ $nf($c->amount) }}</td>
          <td>{{ $c->status === 'credited' ? 'واریز شده' : $c->status }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted" style="text-align:center;padding:1rem">هنوز کمیسیونی واریز نشده.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="panel" style="margin-top:1rem">
  <h3 style="margin-top:0">۱۴ روز اخیر</h3>
  <table class="table">
    <thead>
      <tr>
        <th>تاریخ</th><th>سفارش</th><th>فروش</th><th>سود</th><th>کمیسیون</th>
        @if($canProfit)<th>حاشیه</th>@endif
      </tr>
    </thead>
    <tbody>
    @forelse($byDay as $row)
      <tr>
        <td>{{ $row['date'] }}</td>
        <td>{{ $row['orders'] }}</td>
        <td>{{ $nf($row['gross']) }}</td>
        <td style="color:#047857">{{ $nf($row['profit'] ?? 0) }}</td>
        <td>{{ $nf($row['commission']) }}</td>
        @if($canProfit)
          <td>{{ $row['margin'] }}٪</td>
        @endif
      </tr>
    @empty
      <tr><td colspan="6" class="muted" style="text-align:center;padding:1rem">هنوز فروشی ثبت نشده.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div style="margin-top:1rem">
  <h3>فعالیت‌های اخیر شما</h3>
  <table class="table">
    <thead><tr><th>زمان</th><th>رویداد</th><th>مسیر</th></tr></thead>
    <tbody>
    @forelse(($myActivity ?? collect()) as $log)
      <tr>
        <td>{{ substr((string)$log->created_at,0,16) }}</td>
        <td>{{ $log->action }}</td>
        <td><code style="font-size:.75rem">{{ $log->path }}</code></td>
      </tr>
    @empty
      <tr><td colspan="3" class="muted" style="text-align:center;padding:1rem">هنوز فعالیتی ثبت نشده.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
