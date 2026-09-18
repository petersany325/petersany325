@extends('accounting::layouts.acc')
@section('title','کمیسیون فروش')
@section('content')
@php $money = fn($n) => number_format((int)$n).' تومان'; @endphp
<div class="top"><div><h1>درصد فروشندگان</h1><p>تنظیم کمیسیون و مشاهده فروش تجمیعی</p></div></div>
<div class="panel"><div class="bd" style="padding:0">
<table>
  <thead><tr><th>فروشنده</th><th>نقش</th><th>٪ فعلی</th><th>فروش صادرشده</th><th>کمیسیون</th><th>به‌روزرسانی</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td>{{ $r->name }}</td>
      <td>{{ $r->role }}</td>
      <td>{{ $r->rate }}</td>
      <td>{{ $money($r->sales) }}</td>
      <td>{{ $money($r->commission) }}</td>
      <td>
        <form method="post" action="{{ route('admin.accounting.commissions.update', $r->id) }}" style="display:flex;gap:.4rem;align-items:center">
          @csrf
          <input name="commission_rate" type="number" step="0.01" min="0" max="100" value="{{ $r->rate }}" style="width:90px;border:1px solid var(--line);border-radius:10px;padding:.4rem">
          <button class="btn" type="submit">ذخیره</button>
        </form>
      </td>
    </tr>
  @empty
    <tr><td colspan="6">کارمندی یافت نشد — ابتدا در منوی کارمندان ثبت کنید.</td></tr>
  @endforelse
  </tbody>
</table>
</div></div>
@endsection
