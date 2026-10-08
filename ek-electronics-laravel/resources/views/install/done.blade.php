@extends('layouts.install')
@section('title', 'Installed')
@section('content')
<div class="card">
  <h1>Installation complete</h1>
  <div class="alert alert-ok">Save these admin credentials now — they are shown once.</div>
  <p><strong>Admin email:</strong> {{ $adminEmail }}</p>
  <p><strong>Admin password:</strong> {{ $adminPassword }}</p>
  <p style="margin-top:18px">
    <a class="btn" href="{{ $adminUrl }}">Open staff panel</a>
    <a class="btn" style="background:#0b1220;margin-left:8px" href="{{ $siteUrl }}">Open storefront</a>
  </p>
</div>
@endsection
