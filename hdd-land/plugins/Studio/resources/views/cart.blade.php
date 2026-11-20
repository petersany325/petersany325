@extends('studio::layout')
@section('title', 'سبد خرید | فراز نت آمل')
@section('content')
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb"><a href="{{ url('/computer-accounting#prices') }}">بازگشت به قیمت‌ها</a></p>
    <p class="ag-kicker">کارتابل سبد</p>
    <h1>سبد حسابداری کامپیوتر</h1>
    @if(session('success'))<p class="ag-note">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="ag-note">{{ session('error') }}</p>@endif
    @if(($items ?? []) === [])
      <p class="ag-lead">سبد خالی است. از جدول قیمت، نسخه یا افزونه را اضافه کنید.</p>
    @else
      <div class="ag-table-wrap">
        <table class="ag-cmp ag-cmp--two">
          <thead><tr><th>مورد</th><th>قیمت</th><th></th></tr></thead>
          <tbody>
            @foreach($items as $row)
              <tr>
                <th>{{ $row['title'] }}</th>
                <td>{{ $row['price_label'] }}</td>
                <td>
                  <form method="post" action="{{ url('/computer-accounting/cart/remove') }}">
                    @csrf
                    <input type="hidden" name="sku" value="{{ $row['sku'] }}">
                    <button class="ag-btn ag-btn--line" type="submit">حذف</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="ag-lead">جمع: <b>{{ $totalLabel }}</b></p>
      <div class="ag-actions">
        <a class="ag-btn ag-btn--on" href="{{ url('/pay/cart') }}">ادامه به پرداخت<i>←</i></a>
      </div>
    @endif
  </div>
</section>
@endsection
