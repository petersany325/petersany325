@php $offers = $offers ?? []; @endphp
@if($offers !== [])
<section class="ag-sec" id="sell">
  <div class="ag-wrap">
    <p class="ag-kicker">فروش سایت</p>
    <h2>دکمه فروش و جای قیمت</h2>
    <p class="ag-lead">قیمت هر نوع سایت بعداً اعلام می‌شود. دکمه فروش به صفحه پرداخت واقعی همین سایت می‌رود.</p>
    <div class="ag-cards ag-cards--dense">
      @foreach($offers as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['price_label'] }}</p>
            <a class="ag-btn ag-btn--on" href="{{ url($row['sell_url']) }}">فروش</a>
            <a class="ag-btn ag-btn--line" href="{{ url($row['url']) }}">معرفی</a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif
