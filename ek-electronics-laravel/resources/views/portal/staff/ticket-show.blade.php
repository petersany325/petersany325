@extends('layouts.portal')
@section('title', $ticket->number)
@section('content')
<div class="portal-hero">
  <h1>{{ $ticket->number }}</h1>
  <p>{{ $ticket->subject }} · {{ $ticket->name }} · <span class="badge-soft">{{ $ticket->status }}</span></p>
</div>
<div class="panel">
  @foreach ($ticket->replies as $reply)
    <div class="thread-msg {{ $reply->is_staff ? 'staff' : '' }}">
      <div class="meta">{{ $reply->author_name }} · {{ $reply->created_at?->format('Y-m-d H:i') }} {{ $reply->is_staff ? '· Staff' : '· Customer' }}</div>
      <div>{!! nl2br(e($reply->body)) !!}</div>
    </div>
  @endforeach
</div>
<div class="panel">
  <h2>Staff reply</h2>
  <form class="portal-form" method="post" action="{{ route('staff.tickets.reply', $ticket) }}">
    @csrf
    <label>Status
      <select name="status" required>
        @foreach (['open'=>'Open','pending'=>'Pending','answered'=>'Answered','closed'=>'Closed'] as $k=>$v)
          <option value="{{ $k }}" @selected($ticket->status===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </label>
    <label>Message<textarea name="body" required></textarea></label>
    <button class="btn-portal primary" type="submit">Post reply</button>
  </form>
</div>
@endsection
