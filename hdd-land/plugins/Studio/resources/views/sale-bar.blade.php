@php
  $sku = (string) ($sku ?? '');
  $offer = $sku !== '' && class_exists(\App\Support\StudioOffers::class) ? \App\Support\StudioOffers::find($sku) : null;
  $user = auth()->user();
  $isStaff = $user && ((method_exists($user, 'isStaff') && $user->isStaff()) || (method_exists($user, 'isAdmin') && $user->isAdmin()));
  $quote = ($offer && $isStaff) ? \App\Support\StudioOffers::quote($offer, (int) ($user->id ?? 0)) : null;
  $priced = $offer && (int) ($quote['price'] ?? preg_replace('/\D+/', '', (string) ($offer['price'] ?? ''))) > 0;
@endphp
@if($offer)
<style>
.sale-bar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;margin:.9rem 0 0;padding:.85rem 1rem;border:1px solid rgba(124,92,255,.35);border-radius:16px;background:linear-gradient(135deg,rgba(124,92,255,.16),rgba(255,255,255,.03))}
.sale-bar b{color:#fff;font-size:1rem}
.sale-bar p{margin:.15rem 0 0;color:#c9d2ea;font-size:.86rem}
.sale-bar .ag-btn{margin:0}
</style>
<aside class="sale-bar">
  <div>
    <b>{{ $offer['title'] }}</b>
    <p>
      @if($priced)
        قیمت فروش: {{ $offer['price_label'] }}
      @else
        قیمت این نسخه هنوز اعلام نشده است.
      @endif
      @if($quote)
        <br>
        @if(($quote['source'] ?? '') === 'person')
          درصد سهم شما {{ rtrim(rtrim(number_format((float) $quote['rate'], 1, '.', ''), '0'), '.') }}٪ است، جدا از درصد پیش‌فرض نرم‌افزار.
        @else
          درصد پیش‌فرض این نرم‌افزار {{ rtrim(rtrim(number_format((float) $quote['rate'], 1, '.', ''), '0'), '.') }}٪ است.
        @endif
        @if($priced)
          سهم شما از یک فروش: {{ number_format((int) $quote['share']) }} تومان.
        @else
          سهم بعد از ثبت قیمت حساب می‌شود.
        @endif
      @endif
    </p>
  </div>
  @if($isStaff)
    <a class="ag-btn ag-btn--on" href="{{ url('/staff/desk/sell?sku='.$offer['sku']) }}">{{ $priced ? 'فروش این نسخه' : 'ثبت فروش' }}<i>←</i></a>
  @elseif($priced)
    <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">خرید این نسخه<i>←</i></a>
  @else
    <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست قیمت<i>←</i></a>
  @endif
</aside>
@endif
