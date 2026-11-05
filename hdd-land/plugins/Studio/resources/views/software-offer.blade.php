@php $offer = $offer ?? []; @endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb"><a href="{{ url('/computer-accounting') }}">نرم‌افزارها</a><span>/</span><span>{{ $offer['title'] ?? '' }}</span></p>
    <p class="ag-kicker">نرم‌افزارهای پزشکی</p>
    <h1>{{ $offer['title'] ?? '' }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $offer['short'] ?? '' }}</p>
    <p>قیمت فروش: <b>{{ $offer['price_label'] ?? 'قیمت اعلام می‌شود' }}</b> — سهم نماینده {{ $offer['commission'] ?? 0 }}٪</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url($offer['sell_url'] ?? '/contact') }}">فروش / پرداخت<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">تماس</a>
      <a class="ag-btn ag-btn--line" href="{{ url('/support') }}">پشتیبانی</a>
    </div>
  </div>
</section>
