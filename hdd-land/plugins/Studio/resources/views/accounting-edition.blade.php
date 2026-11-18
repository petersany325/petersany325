@php $edition = $edition ?? []; @endphp
<section class="ag-page">
  <div class="ag-wrap">
    <p class="ag-crumb">
      <a href="{{ url('/computer-accounting') }}">حسابداری کامپیوتر</a>
      <span>/</span>
      <span>{{ $edition['title'] ?? '' }}</span>
    </p>
    <p class="ag-kicker">HDD Land</p>
    <h1>حسابداری {{ $edition['title'] ?? '' }}</h1>
    <p class="ag-lead ag-lead--wide">{{ $edition['sentence'] ?? '' }}</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
    </div>
    <figure class="ag-shot-ph">
      <span>{{ $edition['alt'] ?? '' }}</span>
    </figure>
  </div>
</section>

<section class="ag-sec" id="features">
  <div class="ag-wrap">
    <p class="ag-kicker">این صنف</p>
    <h2>امکانات ویژهٔ این صنف</h2>
    <ul class="ag-list">
      @foreach(($edition['features'] ?? []) as $line)
        <li>{{ $line }}</li>
      @endforeach
    </ul>
  </div>
</section>

<section class="ag-sec" id="shared">
  <div class="ag-wrap">
    <p class="ag-kicker">مشترک</p>
    <h2>به‌علاوهٔ همهٔ امکانات مشترک</h2>
    <p class="ag-lead">سامانهٔ مؤدیان، خزانه، گزارش، چاپ، پیامک و دستیار فارسی در همهٔ نسخه‌ها هست.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--line" href="{{ url('/computer-accounting#shared') }}">امکانات مشترک</a>
    </div>
  </div>
</section>

<section class="ag-sec ag-sec--last">
  <div class="ag-wrap">
    <h2>آمادهٔ دیدن نرم‌افزار هستید؟</h2>
    <p class="ag-lead">یک دموی کوتاه با صنف خودتان نشان‌تان می‌دهیم.</p>
    <div class="ag-actions">
      <a class="ag-btn ag-btn--on" href="{{ url('/contact') }}">درخواست دمو<i>←</i></a>
      <a class="ag-btn ag-btn--line" href="tel:01144447220">تماس: ۰۱۱۴۴۴۴۷۲۲۰</a>
    </div>
  </div>
</section>
