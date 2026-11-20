@php $sku = (string) ($sku ?? ''); @endphp
@if(\App\Support\StudioCart::has($sku))
  <a class="ag-cart is-in" href="{{ url('/computer-accounting/cart') }}">در سبد</a>
@else
  <form method="post" action="{{ url('/computer-accounting/cart/add') }}" style="margin:0">
    @csrf
    <input type="hidden" name="sku" value="{{ $sku }}">
    <button class="ag-cart" type="submit">افزودن به سبد</button>
  </form>
@endif
