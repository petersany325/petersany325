@extends('layouts.store')
@section('title', ($settings['agents_page_title'] ?? 'Agents').' | '.($settings['store_name'] ?? 'EK Electronics'))
@section('content')
<section class="wrap section">
  <div class="kicker" style="color:var(--blue)">{{ $settings['agents_page_kicker'] ?? 'Authorized representation' }}</div>
  <h2>{{ $settings['agents_page_title'] ?? 'Brands we represent' }}</h2>
  <p class="lede">{{ $settings['agents_page_intro'] ?? '' }}</p>

  @if($agencies->isEmpty())
    <p>{{ $settings['agents_empty_text'] ?? 'No active representations are published yet.' }}</p>
  @else
    <div class="agent-list">
      @foreach($agencies as $agency)
        <article class="agent-card {{ $agency->is_featured ? 'is-featured' : '' }}">
          <div class="agent-card-top">
            <div>
              <div class="kicker">{{ $agency->territory }}</div>
              <h3><a href="{{ route('agents.show', $agency->slug) }}">{{ $agency->principal_name }}</a></h3>
              <p class="agent-role">{{ $agency->title }} · {{ $agency->agent_name }}</p>
            </div>
            @if($agency->logo_url)
              <img src="{{ $agency->logo_url }}" alt="{{ $agency->principal_name }}">
            @endif
          </div>
          <p>{{ $agency->excerpt }}</p>
          @if($agency->coverageLines())
            <ul>
              @foreach($agency->coverageLines() as $line)
                <li>{{ $line }}</li>
              @endforeach
            </ul>
          @endif
          <div class="agent-actions">
            <a class="btn btn-primary" href="{{ route('agents.show', $agency->slug) }}">Representation details</a>
            @if($agency->principal_url)
              <a class="btn btn-outline" href="{{ $agency->principal_url }}" target="_blank" rel="noopener">{{ $agency->principal_name }} site</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</section>
@endsection
