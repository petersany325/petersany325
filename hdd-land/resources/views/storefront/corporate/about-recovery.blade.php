@extends('layouts.storefront')
@section('title', 'خدمات تخصصی')
@section('content')
<section class="section">
  <div class="hl-head">
    <div>
      <h2>خدمات تخصصی ذخیره‌سازی</h2>
      <p>مشاوره انتخاب قطعه، تأمین سازمانی و پشتیبانی گارانتی</p>
    </div>
  </div>
  <div class="sub-paths">
    <article class="sub"><h4>مشاوره انتخاب</h4><p>کمک برای انتخاب هارد، SSD و NAS مناسب کارتان.</p></article>
    <article class="sub"><h4>تأمین سازمانی</h4><p>استعلام و تحویل با فاکتور رسمی.</p></article>
    <article class="sub"><h4>گارانتی</h4><p>استعلام سریال و پیگیری وضعیت گارانتی.</p><a href="{{ url('/serial-check') }}">استعلام سریال ←</a></article>
  </div>
</section>
@endsection
