@php
  $staff = $staff ?? [];
  $customer = $customer ?? [];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">نرم‌افزار تعمیرگاه</p>
    <h1>سایت مدیریت تعمیرکاران</h1>
    <p class="ag-lead">پذیرش، ارجاع، تأیید هزینه، انبار، کارمندان و حسابداری تعمیرگاه — همان منوی کارکنان و کارتابل مشتری که قبلاً در سایت بود.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/repair-shop/staff-menu') }}">منوی کارکنان<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/customer-menu') }}">منوی کارتابل مشتری<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">درخواست استقرار<i>←</i></a>
    </div>
    <div class="ag-menu-grid" style="margin-top:1.8rem">
      @foreach($staff as $section)
        <article class="ag-menu-card" id="{{ $section['slug'] }}">
          <small>{{ $section['num'] ?? '' }}</small>
          <a class="ag-menu-card__t" href="{{ url('/repair-shop/m/'.$section['slug']) }}">{{ $section['title'] }}</a>
          <p>{{ $section['short'] ?? '' }}</p>
          @if(!empty($section['children']))
            <ul>
              @foreach($section['children'] as $child)
                <li><a href="{{ url('/repair-shop/m/'.$child['slug']) }}">{{ $child['title'] }}</a></li>
              @endforeach
            </ul>
          @endif
        </article>
      @endforeach
    </div>
    <h2 class="ag-sec-title">کارتابل مشتری</h2>
    <div class="ag-menu-grid ag-menu-grid--3">
      @foreach($customer as $row)
        <a class="ag-menu-card" href="{{ url('/repair-shop/m/'.$row['slug']) }}">
          <small>{{ $row['num'] ?? '' }}</small>
          <b>{{ $row['title'] }}</b>
          <p>{{ $row['short'] ?? '' }}</p>
        </a>
      @endforeach
    </div>
  </div>
</section>
