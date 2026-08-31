@extends('layouts.storefront')
@section('title', 'آموزش تخصصی')
@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>آموزش هارد و ذخیره‌سازی</h2>
      <p>راهنمای انتخاب، نصب و نگهداری تجهیزات ذخیره‌سازی</p>
    </div>
  </div>
  <div class="sub-paths">
    <article class="sub"><h4>انتخاب هارد</h4><p>تفاوت سری‌های دوربین، NAS و سازمانی.</p></article>
    <article class="sub"><h4>SSD و NVMe</h4><p>راهنمای نسل، ظرفیت و سازگاری اسلات.</p></article>
    <article class="sub"><h4>فروشگاه</h4><p>مشاهده موجودی و قیمت محصولات.</p><a href="{{ url('/products') }}">ورود به فروشگاه ←</a></article>
  </div>
</section>
@endsection
