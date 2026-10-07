@extends('layouts.portal')
@section('title', 'My tickets')
@section('content')
@php($portal = 'account')
<div class="portal-hero"><h1>Support tickets</h1><p>Message EK support, sales, recovery, or accounts.</p></div>
<div class="portal-actions"><a class="btn-portal primary" href="{{ route('account.tickets.create') }}">Open ticket</a></div>
<div class="panel">
  @forelse ($tickets as $ticket)
    <a class="list-row" href="{{ route('account.tickets.show', $ticket) }}">
      <div><strong>{{ $ticket->number }}</strong><br><small>{{ $ticket->subject }}</small></div>
      <span class="badge-soft">{{ $ticket->status }}</span>
    </a>
  @empty
    <p>No tickets yet.</p>
  @endforelse
  <div style="margin-top:12px">{{ $tickets->links() }}</div>
</div>
@endsection
