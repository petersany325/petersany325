@extends('layouts.site')
@section('title', 'تیم حقوقی')
@section('content')
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>تیم حقوقی</h2>
      <p>افرادی که پرونده شما را مدیریت و پیگیری می‌کنند.</p>
    </div>
    <div class="grid-3">
      @foreach ($members as $member)
        <article class="plain-block">
          <h3>{{ $member->name }}</h3>
          <p class="meta">{{ $member->role }}</p>
          <p>{{ $member->bio }}</p>
          @if ($member->phone)<p class="meta">{{ $member->phone }}</p>@endif
        </article>
      @endforeach
    </div>
  </div>
</section>
@endsection
