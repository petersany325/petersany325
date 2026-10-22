@php
  $sqlNote = $sqlNote ?? \App\Support\StudioAccountingCopy::sqlNote();
  $industries = $industries ?? [];
  $groups = $groups ?? [];
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-kicker">حسابداری کامپیوتر · SQL Server 2022</p>
    <h1>حسابداری کامپیوتری صنوف</h1>
    <p class="ag-lead ag-lead--wide">هر صنف منوی خودش را دارد: شرکتی، فروشگاهی، سوپرمارکت، پخش، رستوران، تولیدی و بقیه. روی SQL Server 2022 نصب می‌شود و هوش مصنوعی تحلیل و گفتگو را آفلاین، داخل خود نرم‌افزار انجام می‌دهد. حسابداری وب فقط تعمیرکاران است.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست نصب و دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/accounting') }}">حسابداری وب تعمیرکاران</a>
    </div>
    <p class="ag-note">{{ $sqlNote }}</p>
  </div>
</section>

<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <p class="ag-kicker">هوش مصنوعی داخل نرم‌افزار</p>
    <h2>تحلیل و گفتگو با هوش مصنوعی — آفلاین</h2>
    <p class="ag-lead ag-lead--wide">{{ \App\Support\StudioAccountingCopy::aiNote() }}</p>
    <div class="ag-cards ag-cards--dense">
      @foreach(\App\Support\StudioAccountingCopy::aiMenus() as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec" id="industries">
  <div class="ag-wrap">
    <p class="ag-kicker">به تفکیک صنف</p>
    <h2>هر صنف منوی خودش را دارد</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($industries as $row)
        <a class="ag-card" href="{{ url('/computer-accounting/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['short'] }}</p>
            <small>توضیحات کامل ←</small>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <p class="ag-kicker">ماژول‌های دفتر</p>
    <h2>اسناد، انبار، خزانه و گزارش روی SQL Server 2022</h2>
    <div class="ag-menu-grid">
      @foreach($groups as $group)
        <article class="ag-menu-card" id="{{ $group['slug'] }}">
          <small>حسابداری کامپیوتر</small>
          <b>{{ $group['title'] }}</b>
          <ul>
            @foreach($group['items'] as $mod)
              <li><a href="{{ url('/computer-accounting/m/'.$mod['slug']) }}">{{ $mod['title'] }}</a></li>
            @endforeach
          </ul>
        </article>
      @endforeach
    </div>
  </div>
</section>
