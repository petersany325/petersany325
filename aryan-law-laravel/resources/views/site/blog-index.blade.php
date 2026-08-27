@extends('layouts.site')
@section('title', 'مقالات حقوقی | ' . ($settings['site_name'] ?? 'آریان'))
@section('content')
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>مقالات حقوقی</h2>
      <p>نکات کاربردی برای تصمیم‌گیری بهتر در پرونده و قرارداد.</p>
    </div>
    <div class="grid-3">
      @forelse ($posts as $post)
        <article class="plain-block">
          <h3><a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h3>
          <p>{{ $post->excerpt }}</p>
        </article>
      @empty
        <p>هنوز مقاله‌ای منتشر نشده است.</p>
      @endforelse
    </div>
    <div style="margin-top:1.5rem">{{ $posts->links() }}</div>
  </div>
</section>
@endsection
