@php
  $webGroups = $webGroups ?? (class_exists(\App\Support\StudioProductMenus::class) ? \App\Support\StudioProductMenus::webAccountingNav() : []);
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">حسابداری وب · فقط تعمیرکاران</p>
    <h1>حسابداری وب تعمیرکاران</h1>
    <p class="ag-lead ag-lead--wide">حسابداری تحت وب فعلاً فقط برای تعمیرگاه و تعمیرکاران است: سند قبض، معین، بدهکاران و صندوق تعمیرگاه. حسابداری شرکتی، فروشگاهی، رستوران و بقیه صنوف حسابداری کامپیوتری با SQL Server 2022 هستند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/repair-shop') }}">ورود به تعمیرگاه<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر (SQL 2022)</a>
    </div>
    <p class="ag-note">این دفتر به قبض تعمیرگاه وصل است. داده سامانه قبض روی support جدا می‌ماند و از اینجا پاک یا قاطی نمی‌شود.</p>
  </div>
</section>

@foreach($webGroups as $group)
<section class="ag-sec" id="{{ \Illuminate\Support\Str::slug($group['label'] ?? 'web') }}">
  <div class="ag-wrap">
    <p class="ag-kicker">منوی وب</p>
    <h2>{{ $group['label'] }}</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach(($group['children'] ?? []) as $row)
        <a class="ag-card" href="{{ url($row['url']) }}">
          <div>
            <b>{{ $row['label'] }}</b>
            <p>بخش حسابداری تحت وب تعمیرگاه</p>
            <small>باز کردن منو ←</small>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endforeach

<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <p class="ag-kicker">سایر حسابداری‌ها</p>
    <h2>صنوف دیگر روی حسابداری کامپیوتر</h2>
    <p class="ag-lead ag-lead--wide">شرکتی، فروشگاهی، سوپرمارکت، پخش، کافی‌شاپ، رستوران، پیمانکاری، تولیدی، خدماتی و اتوماسیون داخلی در منوی حسابداری کامپیوتر با SQL Server 2022 هستند.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/computer-accounting') }}">مشاهده حسابداری کامپیوتر<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/contact') }}">تماس و استقرار</a>
    </div>
  </div>
</section>
