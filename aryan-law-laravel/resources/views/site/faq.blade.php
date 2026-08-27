@extends('layouts.site')
@section('title', 'سوالات متداول')
@section('content')
<section class="section">
  <div class="container" style="max-width:800px">
    <div class="section-head">
      <h2>سوالات متداول</h2>
      <p>پاسخ‌های کوتاه و شفاف.</p>
    </div>
    <div class="faq-list">
      @foreach ($faqs as $faq)
        <details class="faq-item">
          <summary>{{ $faq->question }}</summary>
          <p>{{ $faq->answer }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>
@endsection
