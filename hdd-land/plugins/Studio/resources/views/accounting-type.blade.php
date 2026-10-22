@php
  $industry = $industry ?? [];
  $industries = $industries ?? [];
  $sqlNote = $sqlNote ?? ($laravelNote ?? \App\Support\StudioAccountingCopy::sqlNote());
@endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>{{ $industry['title'] ?? '' }}</span>
    </p>
    <p class="ag-kicker">{{ $industry['kicker'] ?? 'حسابداری کامپیوتر' }}</p>
    <h1>{{ $industry['title'] ?? '' }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $industry['lead'] ?? '' }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست مشاوره و دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting') }}">→ همه حسابداری‌های کامپیوتر</a>
    </div>
    @if(!empty($industry['highlights']))
      <ul class="ag-list" style="margin-top:1.1rem">
        @foreach($industry['highlights'] as $row)
          <li>{{ $row }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

<section class="ag-sec" id="about">
  <div class="ag-wrap">
    <p class="ag-kicker">توضیح کامل</p>
    <h2>{{ $industry['title'] ?? '' }} چیست و چه کاری انجام می‌دهد؟</h2>
    @foreach(($industry['body'] ?? []) as $para)
      <p class="ag-lead ag-lead--wide">{{ $para }}</p>
    @endforeach
    <p class="ag-note">{{ $sqlNote }}</p>
  </div>
</section>

@php $ai = $industry['ai'] ?? null; @endphp
@if(!empty($ai))
<section class="ag-sec" id="ai">
  <div class="ag-wrap">
    <p class="ag-kicker">هوش مصنوعی داخل نرم‌افزار</p>
    <h2>{{ $ai['title'] ?? 'تحلیل و گفتگو با هوش مصنوعی — آفلاین' }}</h2>
    <p class="ag-lead ag-lead--wide">{{ $ai['lead'] ?? \App\Support\StudioAccountingCopy::aiNote() }}</p>
    <div class="ag-cards ag-cards--dense">
      @foreach(($ai['items'] ?? \App\Support\StudioAccountingCopy::aiMenus()) as $row)
        <article class="ag-card">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['text'] }}</p>
          </div>
        </article>
      @endforeach
    </div>
    <p class="ag-note">{{ \App\Support\StudioAccountingCopy::aiNote() }}</p>
  </div>
</section>
@endif

@if(!empty($industry['buy']))
<section class="ag-sec" id="buy">
  <div class="ag-wrap">
    <p class="ag-kicker">چه می‌خرید</p>
    <h2>{{ $industry['buy']['title'] ?? 'با خرید این نسخه چه چیزی دستتان می‌آید' }}</h2>
    <p class="ag-lead ag-lead--wide">{{ $industry['buy']['lead'] ?? '' }}</p>
    <ul class="ag-list">
      @foreach(($industry['buy']['items'] ?? []) as $row)
        <li>{{ $row }}</li>
      @endforeach
    </ul>
  </div>
</section>
@endif

<section class="ag-sec" id="use">
  <div class="ag-wrap">
    <p class="ag-kicker">کاربرد</p>
    <h2>این حسابداری برای چه کسی و چه کاری است؟</h2>
    <p class="ag-lead ag-lead--wide">{{ $industry['used_for'] ?? '' }}</p>
    @if(!empty($industry['who']))
      <p class="ag-lead ag-lead--wide"><strong>مخاطب اصلی:</strong> {{ $industry['who'] }}</p>
    @endif
    @if(!empty($industry['problems']))
      <h2 class="ag-sec-title">چه مشکلی را حل می‌کند</h2>
      <ul class="ag-list">
        @foreach($industry['problems'] as $row)
          <li>{{ $row }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

<section class="ag-sec" id="menus">
  <div class="ag-wrap">
    <p class="ag-kicker">منوی نرم‌افزار</p>
    <h2>امکانات و منوهای این نسخه — با توضیح هر بخش</h2>
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

@if(!empty($industry['steps']))
<section class="ag-sec" id="steps">
  <div class="ag-wrap">
    <p class="ag-kicker">مسیر پروژه</p>
    <h2>از تحلیل تا نصب روی SQL Server 2022</h2>
    <ol class="ag-steps">
      @foreach($industry['steps'] as $row)
        <li>
          <small>{{ $row['num'] }}</small>
          <b>{{ $row['title'] }}</b>
          <p>{{ $row['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>
@endif

<section class="ag-sec" id="why">
  <div class="ag-wrap">
    <p class="ag-kicker">چرا این پروژه</p>
    <h2>روی SQL Server 2022، مخصوص صنف شما</h2>
    <ul class="ag-list">
      @foreach(($industry['benefits'] ?? []) as $row)
        <li>{{ $row }}</li>
      @endforeach
    </ul>
  </div>
</section>

@if(!empty($industry['faqs']))
<section class="ag-sec" id="faq">
  <div class="ag-wrap">
    <p class="ag-kicker">پرسش‌های رایج</p>
    <h2>قبل از شروع پروژه</h2>
    <ul class="ag-steps" style="grid-template-columns:1fr">
      @foreach($industry['faqs'] as $row)
        <li>
          <b>{{ $row['q'] }}</b>
          <p>{{ $row['a'] }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>
@endif

<section class="ag-sec ag-sec--last" id="more">
  <div class="ag-wrap">
    <p class="ag-kicker">سایر صنوف</p>
    <h2>حسابداری کامپیوتر برای صنوف دیگر</h2>
    <div class="ag-cards ag-cards--dense">
      @foreach($industries as $row)
        @if(($row['slug'] ?? '') === ($industry['slug'] ?? ''))
          @continue
        @endif
        <a class="ag-card" href="{{ url('/computer-accounting/'.$row['slug']) }}">
          <div>
            <b>{{ $row['title'] }}</b>
            <p>{{ $row['short'] }}</p>
            <small>توضیحات کامل ←</small>
          </div>
        </a>
      @endforeach
    </div>
    <div class="ag-actions" style="margin-top:1rem">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست حسابداری کامپیوتر<i>←</i></a>
    </div>
  </div>
</section>
