@extends('layouts.portal')
@section('title', 'Staff tickets')
@section('content')
<div class="portal-hero"><h1>Customer tickets</h1><p>Reply from mobile; updates log to WhatsApp.</p></div>
<div class="panel">
  @forelse ($tickets as $ticket)
    <a class="list-row" href="{{ route('staff.tickets.show', $ticket) }}">
      <div><strong>{{ $ticket->number }}</strong><br><small>{{ $ticket->subject }} · {{ $ticket->name }}</small></div>
      <span class="badge-soft">{{ $ticket->status }}</span>
    </a>
  @empty
    <p>No tickets.</p>
  @endforelse
  <div style="margin-top:12px">{{ $tickets->links() }}</div>
</div>
@endsection
