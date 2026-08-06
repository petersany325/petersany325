@extends('layouts.site')
@section('title', $page->title)
@section('content')
<section class="section">
  <div class="container" style="max-width:760px">
    <h1 style="font-size:clamp(1.8rem,3vw,2.4rem);margin-bottom:1rem">{{ $page->title }}</h1>
    <div class="prose">{!! nl2br(e($page->body)) !!}</div>
  </div>
</section>
@endsection
