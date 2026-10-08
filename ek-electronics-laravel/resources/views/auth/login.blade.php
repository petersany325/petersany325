@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
@php
  $portal = request('portal', 'customer');
  $isStaff = $portal === 'staff';
@endphp
<div class="auth-card">
  <div class="auth-tabs">
    <a href="{{ route('login', ['portal' => 'customer']) }}" class="{{ ! $isStaff ? 'active' : '' }}">Customer</a>
    <a href="{{ route('login', ['portal' => 'staff']) }}" class="{{ $isStaff ? 'active' : '' }}">Staff</a>
  </div>
  <h1>{{ $isStaff ? \App\Models\Setting::getValue('staff_login_heading', 'Staff portal') : \App\Models\Setting::getValue('customer_login_heading', 'Customer sign in') }}</h1>
  <p class="blurb">{{ $isStaff ? \App\Models\Setting::getValue('staff_login_blurb', 'Operations desk for orders, tickets, and accounting.') : \App\Models\Setting::getValue('customer_login_blurb', 'Track orders and support tickets.') }}</p>

  <form class="portal-form" method="post" action="{{ route('login.submit') }}">
    @csrf
    <input type="hidden" name="portal" value="{{ $isStaff ? 'staff' : 'customer' }}">
    <label>Email
      <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
      @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password">
      @error('password')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label style="display:flex;align-items:center;gap:8px;font-weight:500">
      <input type="checkbox" name="remember" value="1" style="width:auto;min-height:auto"> Remember me
    </label>
    <button class="btn-portal primary" type="submit" style="width:100%">Sign in</button>
  </form>

  @if(! $isStaff && ($customerRegisterEnabled ?? true))
    <p class="auth-links">New customer? <a href="{{ route('register') }}">Create an account</a></p>
  @endif
  @if($isStaff)
    <p class="auth-links">Administrators can also use the <a href="{{ url('/admin') }}">full admin panel</a>.</p>
  @endif
</div>
@endsection
