@extends('auth-customers::account.layout')
@section('title','سریال‌ها و گارانتی من')
@section('account')
<div class="acc-card">
  <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start;margin-bottom:1rem">
    <div>
      <h1 style="margin:0 0 .35rem">سریال‌ها و گارانتی من</h1>
      <p style="margin:0;color:var(--muted);font-size:.9rem">سریال‌های خریداری‌شده از سفارش‌ها و فروش حضوری مرتبط با حساب شما</p>
    </div>
    <a class="btn btn-primary btn-sm" href="{{ url('/serial-check') }}">استعلام سریال جدید</a>
  </div>

  @if($serials->isEmpty())
    <div class="alert" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:1rem">
      هنوز سریالی به این حساب متصل نشده است.
      <div style="margin-top:.65rem;display:flex;gap:.45rem;flex-wrap:wrap">
        <a class="btn btn-outline btn-sm" href="{{ route('account.orders') }}">سفارش‌های من</a>
        <a class="btn btn-outline btn-sm" href="{{ url('/serial-check') }}">استعلام گارانتی</a>
        <a class="btn btn-primary btn-sm" href="{{ url('/app/shop') }}">خرید از وب‌اپ</a>
      </div>
    </div>
  @else
    <div style="overflow:auto">
      <table class="acc-table">
        <thead>
          <tr>
            <th>محصول</th>
            <th>سریال</th>
            <th>گارانتی</th>
            <th>سفارش</th>
            <th>تاریخ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($serials as $row)
            <tr>
              <td>{{ $row['product_name'] }}</td>
              <td><code dir="ltr">{{ $row['serial'] }}</code></td>
              <td>
                @if(!empty($row['has_company_warranty']))
                  <span class="acc-badge">{{ $row['warranty_company'] ?: 'گارانتی فعال' }}</span>
                  @if(!empty($row['company_warranty_months']))
                    <small style="display:block;color:var(--muted)">{{ $row['company_warranty_months'] }} ماه</small>
                  @endif
                @else
                  <span style="color:var(--muted)">ثبت‌شده</span>
                @endif
              </td>
              <td>
                @if(!empty($row['order_id']))
                  <a href="{{ route('account.orders.show', $row['order_id']) }}">#{{ $row['order_number'] ?: $row['order_id'] }}</a>
                @else
                  <span style="color:var(--muted)">فروش حضوری</span>
                @endif
              </td>
              <td>
                @if(!empty($row['purchased_at']))
                  {{ class_exists(\Plugins\AdminCore\src\Support\JalaliDate::class)
                    ? \Plugins\AdminCore\src\Support\JalaliDate::format($row['purchased_at'], 'Y/m/d')
                    : \Illuminate\Support\Str::of((string) $row['purchased_at'])->limit(16, '') }}
                @else
                  —
                @endif
              </td>
              <td>
                <a class="btn btn-outline btn-sm" href="{{ $row['lookup_url'] }}">استعلام</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
