@extends('layouts.admin')
@section('title', $accTitle ?? 'حسابداری')
@section('content')
@php
  $m = $accMenus ?? [];
  $money = fn ($n) => number_format((int) $n).' تومان';
  $stats = $stats ?? [];
@endphp
<div class="card" style="padding:1rem 1.1rem 1.2rem">
  <h1 style="margin:0 0 .35rem">{{ $accTitle ?? 'حسابداری HDD Land' }}</h1>
  <p style="color:#5b6b75;margin:0 0 1rem">{{ $subtitle ?? 'داشبورد و منوهای مالی — اگر بخشی در دسترس نباشد همین صفحه باز می‌ماند.' }}</p>
  @if(!empty($stats))
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.7rem;margin-bottom:1rem">
    <div class="card" style="padding:.8rem"><div style="color:#5b6b75;font-size:.8rem">فروش</div><strong>{{ $money($stats['sales_total'] ?? 0) }}</strong></div>
    <div class="card" style="padding:.8rem"><div style="color:#5b6b75;font-size:.8rem">خرید</div><strong>{{ $money($stats['purchase_total'] ?? 0) }}</strong></div>
    <div class="card" style="padding:.8rem"><div style="color:#5b6b75;font-size:.8rem">هزینه</div><strong>{{ $money($stats['expense_total'] ?? 0) }}</strong></div>
    <div class="card" style="padding:.8rem"><div style="color:#5b6b75;font-size:.8rem">پیش‌فاکتور باز</div><strong>{{ (int)($stats['proforma_open'] ?? 0) }}</strong></div>
  </div>
  @endif
  <div style="display:flex;flex-wrap:wrap;gap:.45rem">
    @foreach($m as $it)
      <a class="btn" href="{{ url($it['href']) }}" style="background:#0f6e6a;color:#fff;border-radius:12px;padding:.5rem .8rem;text-decoration:none">{{ $it['label'] }}</a>
    @endforeach
  </div>
</div>
@endsection
