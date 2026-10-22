@php
  $industry = $industry ?? [];
  $industries = $industries ?? [];
  $laravelNote = $laravelNote ?? \App\Support\StudioAccountingCopy::laravelNote();
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/accounting') }}">حسابداری وب</a>
      <span>/</span>
      <span>{{ $industry['title'] ?? '' }}</span>
    </p>
    <p class="ag-kicker">{{ $industry['kicker'] ?? 'حسابداری وب' }}</p>
    <h1>{{ $industry['title'] ?? '' }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $industry['lead'] ?? '' }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره و دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/accounting') }}">→ همه انواع حسابداری</a>
    </div>
  </div>
</section>

<section class="ag-sec" id="use">
  <div class="ag-wrap">
    <p class="ag-kicker">این حسابداری برای چه کاری است؟</p>
    <h2>{{ $industry['title'] ?? '' }} دقیقاً چه کاری را حل می‌کند</h2>
    <p class="ag-lead ag-lead--wide">{{ $industry['used_for'] ?? '' }}</p>
    @if(!empty($industry['who']))
      <p class="ag-lead ag-lead--wide">مخاطب اصلی: {{ $industry['who'] }}</p>
    @endif
    <p class="ag-note">{{ $laravelNote }}</p>
  </div>
</section>

<section class="ag-sec" id="menus">
  <div class="ag-wrap">
    <p class="ag-kicker">منوی نرم‌افزار</p>
    <h2>امکانات و منوهای این نسخه</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach(($industry['menus'] ?? []) as $row)
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

<section class="ag-sec" id="why">
  <div class="ag-wrap">
    <p class="ag-kicker">چرا این پروژه</p>
    <h2>با لاراول، مخصوص کار شما نوشته می‌شود</h2>
    <ul class="ag-list">
      @foreach(($industry['benefits'] ?? []) as $row)
        <li>{{ $row }}</li>
      @endforeach
      <li>پروژه حسابداری شما از پایه با لاراول طراحی و برنامه‌نویسی می‌شود؛ وابسته به نرم‌افزار ویندوزی آماده نیستید.</li>
    </ul>
  </div>
</section>

<section class="ag-sec ag-sec--last" id="more">
  <div class="ag-wrap">
    <p class="ag-kicker">سایر صنوف</p>
    <h2>حسابداری وب برای کسب‌وکارهای دیگر</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($industries as $row)
        @if(($row['slug'] ?? '') === ($industry['slug'] ?? ''))
          @continue
        @endif
        <a class="ag-card" href="{{ url('/accounting/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['short'] }}</p>
            <small>مشاهده توضیحات ←</small>
          </div>
        </a>
      @endforeach
    </div>
    <div class="ag-actions" style="margin-top:1rem">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">شروع پروژه لاراول<i>←</i></a>
    </div>
  </div>
</section>
