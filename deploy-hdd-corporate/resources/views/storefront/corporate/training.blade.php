@extends('layouts.hdd-land')
@section('title', 'آموزش تخصصی')
@section('content')
<section class="section">
  <div class="section-head">
    <h2>آموزش تعمیرات هارد و بازیابی اطلاعات</h2>
    <p>دوره‌های تخصصی برای علاقه‌مندان و تعمیرکاران — قابل اتصال به پکیج نرم‌افزار فروشگاه.</p>
  </div>
  <div class="sub-paths">
    <article class="sub"><h4>تعمیرات هارد</h4><p>آموزش سخت‌افزار و عیب‌یابی تخصصی.</p></article>
    <article class="sub"><h4>بازیابی اطلاعات</h4><p>منطقی/فیزیکی، ابزارها و سناریوهای واقعی.</p></article>
    <article class="sub"><h4>پکیج ترکیبی</h4><p>آموزش + نرم‌افزار از فروشگاه.</p><a href="{{ url('/products') }}">مشاهده فروشگاه ←</a></article>
  </div>
</section>
@endsection
