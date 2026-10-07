@extends('layouts.store')

@section('title', $page->meta_title ?: $page->title.' — EK Electronics')
@section('meta', $page->meta_description ?: $page->title)

@section('content')
<section class="wrap" style="padding-block: 36px 60px; display:grid; gap:28px;">
  <header>
    <h1 style="margin:0;font-size:clamp(1.8rem,4vw,2.6rem);letter-spacing:-0.03em;">{{ $page->title }}</h1>
  </header>
  @foreach ($page->blocks as $block)
    <article class="cms-block cms-{{ $block->type }}">
      @if($block->type === 'hero')
        <div style="border-radius:20px;overflow:hidden;background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;padding:40px 28px;">
          @if($block->heading)<h2 style="margin:0 0 10px;font-size:clamp(1.6rem,3vw,2.2rem);">{{ $block->heading }}</h2>@endif
          @if($block->body)<p style="margin:0 0 16px;max-width:52ch;opacity:.92;">{{ $block->body }}</p>@endif
          @if($block->button_label && $block->button_url)
            <a class="btn btn-primary" href="{{ $block->button_url }}">{{ $block->button_label }}</a>
          @endif
        </div>
      @elseif($block->type === 'image' && $block->image_path)
        <figure style="margin:0;">
          <img src="{{ asset($block->image_path) }}" alt="{{ $block->heading ?: $page->title }}" style="width:100%;border-radius:16px;display:block;">
          @if($block->heading)<figcaption style="margin-top:8px;color:#64748b;">{{ $block->heading }}</figcaption>@endif
        </figure>
      @elseif($block->type === 'html')
        {!! $block->body !!}
      @elseif($block->type === 'cta')
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;padding:22px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;">
          <div>
            @if($block->heading)<h2 style="margin:0 0 6px;">{{ $block->heading }}</h2>@endif
            @if($block->body)<p style="margin:0;color:#64748b;">{{ $block->body }}</p>@endif
          </div>
          @if($block->button_label && $block->button_url)
            <a class="btn btn-primary" href="{{ $block->button_url }}">{{ $block->button_label }}</a>
          @endif
        </div>
      @elseif($block->type === 'faq')
        <details style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;">
          <summary style="font-weight:700;cursor:pointer;">{{ $block->heading }}</summary>
          <div style="margin-top:10px;color:#475569;">{!! nl2br(e($block->body)) !!}</div>
        </details>
      @else
        @if($block->heading)<h2 style="margin:0 0 8px;">{{ $block->heading }}</h2>@endif
        @if($block->body)<div style="color:#334155;line-height:1.65;">{!! nl2br(e($block->body)) !!}</div>@endif
        @if($block->button_label && $block->button_url)
          <p style="margin-top:12px;"><a class="btn btn-primary" href="{{ $block->button_url }}">{{ $block->button_label }}</a></p>
        @endif
      @endif
    </article>
  @endforeach
</section>
@endsection
