@extends('layouts.hdd-land')
@section('title', 'گارانتی هارد')
@section('content')
<section class="section">
  <div class="section-head">
    <h2>قبول گارانتی شرکت‌ها</h2>
    <p>پذیرش گارانتی هارد دیسک برندهای معتبر با مدارک و پیگیری وضعیت.</p>
  </div>
  <div class="sub-paths">
    <article class="sub"><h4>شرایط پذیرش</h4><p>برند، سلامت ظاهری و شرایط گارانتی سازنده.</p></article>
    <article class="sub"><h4>مدارک لازم</h4><p>فاکتور خرید، سریال هارد، کارت گارانتی.</p></article>
    <article class="sub"><h4>ثبت پذیرش</h4><p>از فرم تماس درخواست گارانتی ثبت کنید.</p><a href="{{ url('/contact') }}">ثبت الان ←</a></article>
  </div>
</section>
@endsection
