@extends('layouts.portal')
@section('title', $ticket->number)
@section('content')
@php($portal = 'account')
<div class="portal-hero">
  <h1>{{ $ticket->number }}</h1>
  <p>{{ $ticket->subject }} · <span class="badge-soft">{{ $ticket->status }}</span></p>
</div>
<div class="panel">
  @foreach ($ticket->replies as $reply)
    <div class="thread-msg {{ $reply->is_staff ? 'staff' : '' }}">
      <div class="meta">{{ $reply->author_name }} · {{ $reply->created_at?->format('Y-m-d H:i') }} {{ $reply->is_staff ? '· Staff' : '' }}</div>
      <div>{!! nl2br(e($reply->body)) !!}</div>
    </div>
  @endforeach
</div>
@if ($ticket->status !== 'closed')
<div class="panel">
  <h2>Reply</h2>
  <form class="portal-form" method="post" action="{{ route('account.tickets.reply', $ticket) }}">
    @csrf
    <label>Message<textarea name="body" required></textarea></label>
    <button class="btn-portal primary" type="submit">Send reply</button>
  </form>
</div>
@endif
@endsection
