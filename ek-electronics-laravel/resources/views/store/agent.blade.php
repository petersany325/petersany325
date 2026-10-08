@extends('layouts.store')
@section('title', $agency->principal_name.' · '.$agency->territory.' | '.($settings['store_name'] ?? 'EK Electronics'))
@section('meta', $agency->excerpt)
@section('content')
<section class="wrap section">
  <p><a href="{{ route('agents') }}">← All agents</a></p>
  <div class="kicker" style="color:var(--blue)">{{ $agency->territory }}</div>
  <h2>{{ $agency->principal_name }}</h2>
  <p class="agent-role">{{ $agency->title }} · Represented by {{ $agency->agent_name }}</p>
  <p class="lede">{{ $agency->excerpt }}</p>
  <div class="agent-body">
    @foreach(preg_split("/\r\n|\r|\n/", $agency->body) as $paragraph)
      @if(trim($paragraph) !== '')
        <p>{{ $paragraph }}</p>
      @endif
    @endforeach
  </div>
  @if($agency->coverageLines())
    <h3>What this agency covers</h3>
    <ul class="agent-cover">
      @foreach($agency->coverageLines() as $line)
        <li>{{ $line }}</li>
      @endforeach
    </ul>
  @endif
  <div class="agent-actions">
    @if($agency->email)
      <a class="btn btn-primary" href="mailto:{{ $agency->email }}">Email {{ $agency->agent_name }}</a>
    @endif
    @if($agency->phone)
      <a class="btn btn-outline" href="tel:{{ preg_replace('/\s+/', '', $agency->phone) }}">{{ $agency->phone }}</a>
    @endif
    @if($agency->website)
      <a class="btn btn-outline" href="{{ $agency->website }}" target="_blank" rel="noopener">Brand website</a>
    @endif
    <a class="btn btn-wa" href="https://wa.me/{{ $settings['whatsapp'] }}?text={{ urlencode('Hi EK Electronics, I need '.$agency->principal_name.' licensing help in '.$agency->territory.'.') }}">WhatsApp the agent</a>
  </div>
</section>
@endsection
