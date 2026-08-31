@extends('auth-customers::account.layout')
@section('title','جزئیات سفارش')
@section('account')
<div class="acc-card">
  <h1>سفارش {{ $order->order_number }}</h1>
  <p style="color:var(--muted);font-size:.9rem;margin:0 0 1rem">
    وضعیت: <span class="acc-badge">{{ $order->statusLabel() }}</span>
    · تاریخ شمسی: {{ \Plugins\AdminCore\src\Support\JalaliDate::format($order->created_at, 'Y/m/d H:i') }}
  </p>
  <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:1rem">
    <a class="btn btn-primary btn-sm" href="{{ route('account.invoices.show', ['order' => $order->id, 'type' => 'invoice']) }}" target="_blank">مشاهده فاکتور</a>
    <a class="btn btn-outline btn-sm" href="{{ route('account.invoices.show', ['order' => $order->id, 'type' => 'proforma']) }}" target="_blank">پیش‌فاکتور</a>
    <a class="btn btn-outline btn-sm" href="{{ route('account.invoices') }}">همه فاکتورها</a>
  </div>
  <table class="acc-table">
    <thead><tr><th>محصول</th><th>سریال</th><th>تعداد</th><th>قیمت</th><th>جمع</th></tr></thead>
    <tbody>
    @foreach($order->items as $item)
      <tr>
        <td>{{ $item->product_name ?? '—' }}</td>
        <td>
          @if(!empty($item->serial))
            <code dir="ltr">{{ $item->serial }}</code>
            <div style="margin-top:.35rem">
              <a class="btn btn-outline btn-sm" href="{{ url('/serial-check?serial='.urlencode($item->serial)) }}">استعلام گارانتی</a>
            </div>
          @else
            <span style="color:var(--muted)">—</span>
          @endif
        </td>
        <td>{{ $item->quantity ?? 1 }}</td>
        <td>{{ number_format((float)($item->unit_price ?? 0)) }}</td>
        <td>{{ number_format((float)($item->line_total ?? 0)) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <p style="margin-top:1rem">ارسال: {{ $order->shipping_title ?: '—' }} · {{ number_format((float)$order->shipping_cost) }} تومان</p>
  <p style="font-weight:800">جمع کل: {{ number_format((float)$order->total) }} تومان</p>
  <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:1rem">
    <a class="btn btn-outline btn-sm" href="{{ route('account.orders') }}">بازگشت</a>
    <a class="btn btn-primary btn-sm" href="{{ route('account.serials') }}">همه سریال‌های من</a>
  </div>
</div>
@endsection
