@php
  $kind = $kind ?? 'staff';
  $isStaff = $kind === 'staff';
  $rows = $isStaff ? ($staff ?? []) : ($customer ?? []);
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">{{ $isStaff ? 'منوی کارکنان' : 'منوی کارتابل مشتری' }}</p>
    <h1>{{ $isStaff ? 'همه منوهای تعمیرگاه برای نیرو' : 'پرتال مشتری تعمیرگاه' }}</h1>
    <p class="ag-lead">{{ $isStaff ? 'میز کار، پذیرش، ارجاع، انبار، کارمندان، حسابداری و گزارش — مثل منوی قبلی سایت.' : 'پیگیری قبض، تأیید هزینه، پرداخت، گارانتی و پیام.' }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop') }}">→ صفحه محصول</a>
      @if($isStaff)
        <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/customer-menu') }}">منوی کارتابل مشتری ←</a>
      @else
        <a class="ag-btn ag-btn--line" href="{{ url('/repair-shop/staff-menu') }}">منوی کارکنان ←</a>
      @endif
    </div>
    <div class="ag-menu-grid" style="margin-top:1.8rem">
      @foreach($rows as $section)
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
  </div>
</section>
