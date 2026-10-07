@extends('layouts.install')
@section('title', 'Install EK Electronics')
@section('content')
<div class="card">
  <h1>Install EK Electronics</h1>
  <p class="lead">Enter MySQL details. The installer creates tables, seeds the catalogue, and creates the admin account.</p>
  @if (session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
  <h2 style="font-size:1.1rem;margin:1.5rem 0 .75rem">Requirements</h2>
  <ul class="req">
    @foreach ($requirements as $label => $ok)
      <li><span>{{ $label }}</span><span class="{{ $ok ? 'ok' : 'bad' }}">{{ $ok ? 'OK' : 'Fix required' }}</span></li>
    @endforeach
  </ul>
  @if (! $ready)
    <div class="alert alert-error">Fix the requirements above, then refresh.</div>
  @else
    <form method="post" action="{{ route('install.store') }}">
      @csrf
      <div class="row">
        <label>DB host <input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required></label>
        <label>Port <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required></label>
      </div>
      <label>Database <input type="text" name="db_database" value="{{ old('db_database', 'ekeledlx_site') }}" required></label>
      <div class="row">
        <label>Username <input type="text" name="db_username" value="{{ old('db_username', 'ekeledlx_siteuser') }}" required></label>
        <label>Password <input type="password" name="db_password" value="{{ old('db_password') }}"></label>
      </div>
      @if ($errors->any())
        <div class="alert alert-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      <button class="btn" type="submit">Install &amp; create admin</button>
    </form>
  @endif
</div>
@endsection
