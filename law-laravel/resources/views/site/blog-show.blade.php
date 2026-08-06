@extends('layouts.site')
@section('title', $post->title)
@section('content')
<section class="section">
  <div class="container" style="max-width:760px">
    <p class="meta"><a href="{{ route('blog.index') }}">← بازگشت به مقالات</a></p>
    <h1 style="font-size:clamp(1.8rem,3vw,2.6rem);margin:0.5rem 0 1rem">{{ $post->title }}</h1>
    <div class="prose">{!! nl2br(e($post->body)) !!}</div>
  </div>
</section>
@endsection
