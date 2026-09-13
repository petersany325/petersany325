@extends('accounting::layouts.account')
@section('title', 'درخواست اقساط')
@section('content')
<header>
  <h1>فرم درخواست اقساط</h1>
  <a href="{{ route('account.installments') }}">بازگشت</a>
</header>
@if(session('error'))<div class="card" style="border-color:#b42318;color:#b42318">{{ session('error') }}</div>@endif

<div class="card">
  <form method="get" action="{{ route('account.installments.create') }}" style="display:flex;gap:.5rem;margin-bottom:1rem">
    <input name="q" value="{{ $q }}" placeholder="جستجوی کالا" style="flex:1;border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    <button type="submit" style="border:0;border-radius:12px;padding:.6rem .9rem;background:var(--brand);color:#fff;font:inherit">جستجو</button>
  </form>
  @if($products->isNotEmpty())
    <div style="display:grid;gap:.45rem;margin-bottom:1rem;max-height:220px;overflow:auto">
      @foreach($products as $p)
        <a href="{{ route('account.installments.create', ['product_id'=>$p->id,'q'=>$q]) }}" style="display:flex;justify-content:space-between;padding:.55rem .7rem;border:1px solid var(--line);border-radius:12px;text-decoration:none;color:inherit;{{ isset($selected) && (int)$selected->id===(int)$p->id ? 'background:#e8f3f2' : '' }}">
          <span>{{ $p->name }}@if(!empty($p->sku)) <small style="color:var(--muted)">({{ $p->sku }})</small>@endif</span>
          <strong>{{ number_format((int)($p->price ?? 0)) }}</strong>
        </a>
      @endforeach
    </div>
  @endif
</div>

<div class="card">
  <form method="post" action="{{ route('account.installments.store') }}">
    @csrf
    <input type="hidden" name="product_id" value="{{ $selected->id ?? old('product_id') }}">
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">عنوان کالا
      <input name="product_title" required value="{{ old('product_title', $selected->name ?? '') }}" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    </label>
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">قیمت (تومان)
      <input name="product_price" required value="{{ old('product_price', $selected->price ?? '') }}" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    </label>
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">پیش‌پرداخت
      <input name="down_payment" value="{{ old('down_payment', 0) }}" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    </label>
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">تعداد اقساط (۱ تا ۲۴)
      <input name="months" type="number" min="1" max="24" value="{{ old('months', 3) }}" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    </label>
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">کد ملی (اختیاری)
      <input name="customer_national_id" value="{{ old('customer_national_id') }}" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit">
    </label>
    <label style="display:grid;gap:.3rem;margin-bottom:.75rem;font-size:.85rem;color:var(--muted)">توضیح / درخواست
      <textarea name="customer_note" rows="3" style="border:1px solid var(--line);border-radius:12px;padding:.6rem;font:inherit" placeholder="توضیح برای پشتیبانی و فروش">{{ old('customer_note') }}</textarea>
    </label>
    <p style="font-size:.82rem;color:var(--muted);margin:0 0 .8rem">با ثبت، درخواست در پنل حسابداری ثبت می‌شود و در صورت وجود سامانه تیکت، یک تیکت پشتیبانی هم باز می‌شود.</p>
    <button type="submit" style="width:100%;border:0;border-radius:12px;padding:.75rem;background:var(--brand);color:#fff;font:inherit;font-weight:700">ثبت درخواست اقساط</button>
  </form>
</div>
@endsection
